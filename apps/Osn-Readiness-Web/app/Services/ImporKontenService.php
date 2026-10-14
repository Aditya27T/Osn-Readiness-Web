<?php

namespace App\Services;

use App\Contracts\Services\ImporKontenServiceInterface;
use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Guards\SoalGuard;
use App\Models\Kompetensi;
use App\Models\KonteksSoal;
use App\Models\Materi;
use App\Models\Pembahasan;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImporKontenService implements ImporKontenServiceInterface
{
    public function impor(string $folder, bool $dryRun = false, ?string $tingkat = null): array
    {
        $folderMateri = $folder.'/materi';

        if (! is_dir($folderMateri)) {
            throw new RuntimeException("Folder impor tidak ditemukan: {$folder}");
        }

        $berkasMateri = glob($folderMateri.'/*.md') ?: [];
        $berkasSoal = glob($folder.'/soal_*.json') ?: [];

        if ($berkasMateri === [] && $berkasSoal === []) {
            throw new RuntimeException("Tidak ada berkas materi atau soal di {$folder}");
        }

        // Penyaringan tingkat dilakukan sebelum pemeriksaan id_sumber kembar,
        // supaya impor satu tingkat tidak gagal gara-gara berkas tingkat lain.
        if ($tingkat !== null) {
            $berkasMateri = $this->saringMateri($berkasMateri, $tingkat);
        }

        $this->tolakIdSumberMateriKembar($berkasMateri);

        $soalDikumpulkan = [];

        foreach ($berkasSoal as $berkas) {
            $isi = json_decode((string) file_get_contents($berkas), true, 512, JSON_THROW_ON_ERROR);

            foreach ($isi as $baris) {
                if ($tingkat !== null && strtolower((string) ($baris['tingkat'] ?? '')) !== strtolower($tingkat)) {
                    continue;
                }

                $idSumber = $baris['id_sumber'] ?? null;

                if (! is_string($idSumber) || $idSumber === '') {
                    throw new RuntimeException("Soal tanpa id_sumber di {$berkas}.");
                }

                // id_sumber kembar di berkas mana pun membatalkan seluruh
                // impor: kita tak tahu mana yang benar.
                if (isset($soalDikumpulkan[$idSumber])) {
                    throw new RuntimeException("id_sumber kembar: {$idSumber}. Impor dibatalkan seluruhnya.");
                }

                $soalDikumpulkan[$idSumber] = $baris;
            }
        }

        if ($berkasMateri === [] && $soalDikumpulkan === []) {
            throw new RuntimeException("Tidak ada materi atau soal tingkat {$tingkat} di {$folder}");
        }

        // Dry-run memakai transaksi yang di-rollback di akhir, sehingga
        // seluruh alur dan validasinya jalan sungguhan tanpa menyisakan data.
        if ($dryRun) {
            DB::beginTransaction();

            $laporan = $this->proses($folder, $berkasMateri, $soalDikumpulkan, true);

            DB::rollBack();

            return $laporan;
        }

        return DB::transaction(fn (): array => $this->proses($folder, $berkasMateri, $soalDikumpulkan, false));
    }

    /**
     * Ambil hanya berkas materi milik satu tingkat, dibaca dari frontmatter
     * `tingkat:`. Berkas tanpa frontmatter tingkat ikut serta supaya parsing
     * berikutnya yang melaporkan kekeliruan.
     *
     * @param  list<string>  $berkasMateri
     * @return list<string>
     */
    private function saringMateri(array $berkasMateri, string $tingkat): array
    {
        $saring = array_map('strtolower', ['kabupaten', 'provinsi']);
        $target = strtolower($tingkat);

        return array_values(array_filter(
            $berkasMateri,
            function (string $berkas) use ($target, $saring): bool {
                $isi = (string) file_get_contents($berkas);

                if (preg_match('/^---\n(.+?)\n---\n/s', $isi, $potongan) !== 1) {
                    return true;
                }

                if (preg_match('/^tingkat:\s*(.+)$/m', $potongan[1], $cocok) !== 1) {
                    return true;
                }

                // Kode short (kab/prov) disamakan dengan nama panjang supaya
                // pemanggil tidak harus ingat dua ejaan.
                $nilai = strtolower(trim($cocok[1]));
                $nilai = match ($nilai) {
                    'kab' => 'kabupaten',
                    'prov' => 'provinsi',
                    default => $nilai,
                };

                return $nilai === $target || in_array($nilai, $saring, true) === false;
            },
        ));
    }

    /**
     * id_sumber materi yang kembar membatalkan seluruh impor, sama seperti
     * soal: kita tak tahu berkas mana yang benar.
     *
     * @param  list<string>  $berkasMateri
     */
    private function tolakIdSumberMateriKembar(array $berkasMateri): void
    {
        $terlihat = [];

        foreach ($berkasMateri as $berkas) {
            if (preg_match('/^id_sumber:\s*(.+)$/m', (string) file_get_contents($berkas), $cocok) !== 1) {
                continue;
            }

            $idSumber = trim($cocok[1]);

            if (isset($terlihat[$idSumber])) {
                throw new RuntimeException("id_sumber materi kembar: {$idSumber}. Impor dibatalkan seluruhnya.");
            }

            $terlihat[$idSumber] = true;
        }
    }

    /**
     * @param  list<string>  $berkasMateri
     * @param  array<string, array<string, mixed>>  $soalDikumpulkan
     * @return array<string, mixed>
     */
    private function proses(string $folder, array $berkasMateri, array $soalDikumpulkan, bool $dryRun): array
    {
        $laporan = [
            'materi' => ['masuk' => 0, 'diperbarui' => 0],
            'soal' => [
                'masuk' => 0,
                'diperbarui' => 0,
                'ditolak' => [],
                'dilewati' => [],
            ],
            'dry_run' => $dryRun,
        ];

        foreach ($berkasMateri as $berkas) {
            $this->imporMateri($folder, $berkas, $laporan, $dryRun);
        }

        foreach ($soalDikumpulkan as $idSumber => $baris) {
            $this->imporSoal($folder, $idSumber, $baris, $laporan, $dryRun);
        }

        return $laporan;
    }

    /**
     * @param  array<string, mixed>  $laporan
     */
    private function imporMateri(string $folder, string $berkas, array &$laporan, bool $dryRun): void
    {
        $isi = (string) file_get_contents($berkas);

        if (! preg_match('/^---\n(.+?)\n---\n(.*)$/s', $isi, $potongan)) {
            throw new RuntimeException("Frontmatter tidak ditemukan di {$berkas}.");
        }

        $metadata = [];

        foreach (explode("\n", $potongan[1]) as $baris) {
            $titikDua = strpos($baris, ':');

            if ($titikDua === false) {
                continue;
            }

            $kunci = trim(substr($baris, 0, $titikDua));
            $metadata[$kunci] = trim(substr($baris, $titikDua + 1));
        }

        $idSumber = $metadata['id_sumber'] ?? null;
        $tingkatKode = $metadata['tingkat'] ?? null;

        if (! $idSumber || ! $tingkatKode) {
            throw new RuntimeException("id_sumber atau tingkat hilang di {$berkas}.");
        }

        $tingkat = $this->cariTingkat((string) $tingkatKode);
        $isiMateri = trim($potongan[2]);

        $isiMateri = $this->salinGambarMateri($folder, $isiMateri, $dryRun);

        $namaKompetensi = (string) ($metadata['kompetensi'] ?? 'Umum');
        $kompetensi = Kompetensi::query()
            ->where('tingkat_id', $tingkat->id)
            ->where('nama_kompetensi', $namaKompetensi)
            ->first();

        if (! $kompetensi instanceof Kompetensi) {
            $kompetensi = Kompetensi::create([
                'tingkat_id' => $tingkat->id,
                'nama_kompetensi' => $namaKompetensi,
            ]);
        }

        $ada = Materi::query()->where('id_sumber', $idSumber)->first();

        if ($ada instanceof Materi) {
            $ada->update([
                'kompetensi_id' => $kompetensi->id,
                'urutan' => $this->urutanUntukPembaruan($tingkat->id, $ada, (int) ($metadata['urutan'] ?? $ada->urutan)),
                'judul' => (string) ($metadata['judul'] ?? $ada->judul),
                'isi_materi' => $isiMateri,
            ]);

            $laporan['materi']['diperbarui']++;
        } else {
            Materi::create([
                'id_sumber' => $idSumber,
                'tingkat_id' => $tingkat->id,
                'kompetensi_id' => $kompetensi->id,
                'urutan' => $this->urutanBebas($tingkat->id, (int) ($metadata['urutan'] ?? 1)),
                'judul' => (string) ($metadata['judul'] ?? $idSumber),
                'isi_materi' => $isiMateri,
            ]);

            $laporan['materi']['masuk']++;
        }
    }

    /**
     * Saat memperbarui, urutan dari berkas dipakai hanya bila slotnya masih
     * bebas. Bila sudah dipakai materi lain, urutan lama dipertahankan;
     * memindahkan urutan materi yang sudah ada bukan urusan impor.
     */
    private function urutanUntukPembaruan(int $tingkatId, Materi $materi, int $diinginkan): int
    {
        if ($diinginkan === (int) $materi->urutan) {
            return $diinginkan;
        }

        $terpakai = Materi::query()
            ->where('tingkat_id', $tingkatId)
            ->whereKeyNot($materi->id)
            ->pluck('urutan')
            ->map(fn ($urutan): int => (int) $urutan)
            ->all();

        return in_array($diinginkan, $terpakai, true)
            ? (int) $materi->urutan
            : $diinginkan;
    }

    /**
     * Urutan harus unik per tingkat. Bila nomor dari berkas sudah dipakai
     * materi lain, ambil slot kosong berikutnya; identitas materi tetap
     * id_sumber, urutan hanya untuk urutan tampil.
     */
    private function urutanBebas(int $tingkatId, int $diinginkan): int
    {
        $terpakai = Materi::query()
            ->where('tingkat_id', $tingkatId)
            ->pluck('urutan')
            ->map(fn ($urutan): int => (int) $urutan)
            ->all();

        if (! in_array($diinginkan, $terpakai, true)) {
            return $diinginkan;
        }

        $urutan = 1;

        while (in_array($urutan, $terpakai, true)) {
            $urutan++;
        }

        return $urutan;
    }

    /**
     * @param  array<string, mixed>  $laporan
     * @param  array<string, mixed>  $baris
     */
    private function imporSoal(string $folder, string $idSumber, array $baris, array &$laporan, bool $dryRun): void
    {
        $tingkat = $this->cariTingkat((string) ($baris['tingkat'] ?? ''));

        $materi = Materi::query()
            ->where('id_sumber', (string) ($baris['materi'] ?? ''))
            ->first();

        if (! $materi instanceof Materi) {
            $laporan['soal']['ditolak'][] = [
                'id_sumber' => $idSumber,
                'alasan' => "Materi {$baris['materi']} tidak ditemukan.",
            ];

            return;
        }

        if ((int) $materi->tingkat_id !== (int) $tingkat->id) {
            $laporan['soal']['ditolak'][] = [
                'id_sumber' => $idSumber,
                'alasan' => "Materi {$baris['materi']} bukan dari tingkat {$baris['tingkat']}.",
            ];

            return;
        }

        // Nilai di luar daftar menolak soal ini saja; impor yang lain tetap
        // jalan (BE-21).
        $level = Level::tryFrom((string) ($baris['tingkat_kesulitan'] ?? ''));

        if (! $level instanceof Level) {
            $laporan['soal']['ditolak'][] = [
                'id_sumber' => $idSumber,
                'alasan' => 'tingkat_kesulitan harus mudah, sedang, atau sulit.',
            ];

            return;
        }

        $peruntukan = Peruntukan::tryFrom((string) ($baris['peruntukan'] ?? ''));

        if (! $peruntukan instanceof Peruntukan) {
            $laporan['soal']['ditolak'][] = [
                'id_sumber' => $idSumber,
                'alasan' => 'peruntukan harus pretest, latihan, atau simulasi.',
            ];

            return;
        }

        $pilihan = $baris['pilihan'] ?? null;
        $isian = ! is_array($pilihan) || $pilihan === [];

        if (! $isian) {
            if (count($pilihan) < 2) {
                $laporan['soal']['ditolak'][] = [
                    'id_sumber' => $idSumber,
                    'alasan' => 'Pilihan ganda butuh minimal dua pilihan.',
                ];

                return;
            }

            $kunci = (string) ($baris['jawaban_benar'] ?? '');

            if (! array_key_exists($kunci, $pilihan)) {
                $laporan['soal']['ditolak'][] = [
                    'id_sumber' => $idSumber,
                    'alasan' => "Kunci jawaban {$kunci} bukan salah satu pilihan.",
                ];

                return;
            }
        }

        $gambar = null;

        if (! empty($baris['gambar'])) {
            $salinan = $this->salinGambarSoal($folder, (string) $baris['gambar'], $dryRun);

            if ($salinan === null) {
                $laporan['soal']['ditolak'][] = [
                    'id_sumber' => $idSumber,
                    'alasan' => "Gambar {$baris['gambar']} tidak ada di folder gambar/soal.",
                ];

                return;
            }

            $gambar = $salinan;
        }

        $konteks = null;

        if (! empty($baris['deskripsi_soal'])) {
            $teksKonteks = (string) $baris['deskripsi_soal'];

            $konteks = KonteksSoal::query()
                ->where('tingkat_id', $tingkat->id)
                ->where('isi_konteks', $teksKonteks)
                ->first();

            if (! $konteks instanceof KonteksSoal) {
                $konteks = KonteksSoal::create([
                    'tingkat_id' => $tingkat->id,
                    'judul' => mb_substr($teksKonteks, 0, 200),
                    'isi_konteks' => $teksKonteks,
                ]);
            }
        }

        $data = [
            'id_sumber' => $idSumber,
            'tingkat_id' => (int) $tingkat->id,
            'materi_id' => (int) $materi->id,
            'konteks_id' => $konteks?->id,
            'level' => $level->value,
            'peruntukan' => $peruntukan->value,
            'tipe_soal' => $isian ? 'isian' : 'pilihan_ganda',
            'pertanyaan' => (string) $baris['soal'],
            'pilihan_jawaban' => $isian ? null : $pilihan,
            'kunci_jawaban' => $isian
                ? (string) ($baris['jawaban_benar'] ?? '')
                : (string) $baris['jawaban_benar'],
            'gambar' => $gambar,
        ];

        $ada = Soal::withTrashed()->where('id_sumber', $idSumber)->first();

        if ($ada instanceof Soal) {
            $berubah = $this->perubahanYangMenyimpang($ada, $data);
            $terlarang = array_intersect(array_keys($berubah), SoalGuard::KOLOM_TERKUNCI);

            if ($terlarang !== [] && SoalGuard::isInUse($ada)) {
                // Soal terpakai: kunci, level, peruntukan, dan materinya
                // dikunci, tetapi teks pertanyaan tetap boleh diperbaiki
                // (BE-21). Perubahan terlarang dilaporkan dan dibuang.
                $boleh = array_diff(array_keys($berubah), $terlarang);

                if ($boleh !== []) {
                    $ada->update(array_intersect_key($berubah, array_flip($boleh)));
                    $laporan['soal']['diperbarui']++;
                }

                $laporan['soal']['dilewati'][] = [
                    'id_sumber' => $idSumber,
                    'alasan' => 'Soal sudah dipakai pengerjaan; kunci/level/peruntukan/materi tidak diubah.',
                ];

                $ada->refresh();

                $this->simpanPembahasan($ada, $baris);

                return;
            }

            $ada->update($berubah);
            $laporan['soal']['diperbarui']++;
        } else {
            $soal = Soal::create($data);
            $ada = $soal;
            $laporan['soal']['masuk']++;
        }

        $this->simpanPembahasan($ada, $baris);
    }

    /**
     * Pembahasan masuk lewat tabel sendiri, satu baris per soal.
     *
     * @param  array<string, mixed>  $baris
     */
    private function simpanPembahasan(Soal $soal, array $baris): void
    {
        if (empty($baris['pembahasan'])) {
            return;
        }

        Pembahasan::updateOrCreate(
            ['soal_id' => $soal->id],
            ['isi_pembahasan' => $this->bersihkanTeks((string) $baris['pembahasan'])],
        );
    }

    /**
     * Buang artefak scraping sebelum teks disimpan.
     *
     * `[cite: N]` adalah penanda rujukan dari sumber web hasil scraping, ada di
     * sebagian besar pembahasan kabupaten dan tidak pernah ditampilkan sebagai
     * isi. Sisanya dibiarkan apa adanya supaya teks sumber tetap utuh.
     */
    private function bersihkanTeks(string $teks): string
    {
        $teks = (string) preg_replace('/\s*\[cite:\s*\d+\]/', '', $teks);

        return trim($teks);
    }

    /**
     * Perbandingan nilai baru melawan nilai tersimpan; hanya kolom yang
     * benar-benar berubah yang dikirim ke update.
     *
     * @return array<string, mixed>
     */
    private function perubahanYangMenyimpang(Soal $ada, array $data): array
    {
        $beda = [];

        foreach ($data as $kunci => $nilai) {
            $kini = $ada->getAttribute($kunci);

            if ($kini instanceof \BackedEnum) {
                $kini = $kini->value;
            }

            // Kolom jsonb dibandingkan sebagai JSON, bukan string mentah.
            if (is_array($kini) || is_array($nilai)) {
                if (json_encode($kini) !== json_encode($nilai)) {
                    $beda[$kunci] = $nilai;
                }

                continue;
            }

            if ((string) $kini !== (string) $nilai) {
                $beda[$kunci] = $nilai;
            }
        }

        return $beda;
    }

    private function cariTingkat(string $kode): TingkatSeleksi
    {
        $urutan = match (strtolower($kode)) {
            'kabupaten', 'kab' => 1,
            'provinsi', 'prov' => 2,
            default => 0,
        };

        $tingkat = TingkatSeleksi::query()->where('urutan', $urutan)->first();

        if (! $tingkat instanceof TingkatSeleksi) {
            throw new RuntimeException("Tingkat {$kode} tidak ditemukan.");
        }

        return $tingkat;
    }

    /**
     * Gambar materi di Markdown menunjuk berkas sumber; salin ke storage
     * publik dan tulis ulang alamatnya supaya bisa dibuka dari web.
     */
    private function salinGambarMateri(string $folder, string $isiMateri, bool $dryRun): string
    {
        return (string) preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)]+)\)/',
            function (array $cocok) use ($folder, $dryRun): string {
                $nama = basename($cocok[2]);
                $sumber = $folder.'/gambar/materi/'.$nama;

                if (! is_file($sumber)) {
                    return $cocok[0];
                }

                $tujuan = 'materi/'.$nama;

                // Dry-run tidak menulis berkas fisik; alamat di teks tetap
                // ditulis ulang supaya laporannya realistis.
                if (! $dryRun && ! Storage::disk('public')->exists($tujuan)) {
                    Storage::disk('public')->put($tujuan, (string) file_get_contents($sumber));
                }

                // Alt teks dirakit dengan concatenation. String dobel-kutip
                // akan membaca $1 sebagai variabel PHP, bukan backreference.
                return '!['.$cocok[1].'](/storage/'.$tujuan.')';
            },
            $isiMateri,
        );
    }

    private function salinGambarSoal(string $folder, string $nama, bool $dryRun): ?string
    {
        $sumber = $folder.'/gambar/soal/'.$nama;

        if (! is_file($sumber)) {
            return null;
        }

        $tujuan = 'soal/'.$nama;

        if (! $dryRun && ! Storage::disk('public')->exists($tujuan)) {
            Storage::disk('public')->put($tujuan, (string) file_get_contents($sumber));
        }

        return $tujuan;
    }
}
