<?php

namespace App\Services;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Contracts\Repositories\HasilSimulasiJawabanRepositoryInterface;
use App\Contracts\Repositories\HasilSimulasiRepositoryInterface;
use App\Contracts\Repositories\KenaikanTingkatRepositoryInterface;
use App\Contracts\Repositories\PretestRepositoryInterface;
use App\Contracts\Repositories\SimulasiRepositoryInterface;
use App\Contracts\Repositories\SoalRepositoryInterface;
use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Services\AturanServiceInterface;
use App\Contracts\Services\KelulusanServiceInterface;
use App\Contracts\Services\PutaranServiceInterface;
use App\Contracts\Services\SimulasiServiceInterface;
use App\Contracts\Services\SoalPickerServiceInterface;
use App\Contracts\Services\SyaratSimulasiServiceInterface;
use App\DTOs\PermintaanSoal;
use App\DTOs\SimulasiDimulai;
use App\Enums\JenisPengerjaan;
use App\Enums\Peruntukan;
use App\Exceptions\KuotaSimulasiHabisException;
use App\Exceptions\PengerjaanSudahDisubmitException;
use App\Exceptions\PerhitunganTidakTersediaException;
use App\Exceptions\SimulasiBelumDinilaiException;
use App\Exceptions\SudahLulusException;
use App\Exceptions\SyaratSimulasiBelumTerpenuhiException;
use App\Exceptions\TingkatTerkunciException;
use App\Exceptions\WaktuHabisException;
use App\Jobs\NilaiUlangJob;
use App\Models\HasilSimulasi;
use App\Models\HasilSimulasiJawaban;
use App\Models\Pretest;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SimulasiService implements SimulasiServiceInterface
{
    /** Toleransi keterlambatan jawaban setelah batas (detik, keputusan #3). */
    private const TOLERANSI_DETIK = 30;

    public function __construct(
        private readonly HasilSimulasiRepositoryInterface $hasilRepository,
        private readonly HasilSimulasiJawabanRepositoryInterface $jawabanRepository,
        private readonly PretestRepositoryInterface $pretestRepository,
        private readonly KenaikanTingkatRepositoryInterface $kenaikanRepository,
        private readonly SoalRepositoryInterface $soalRepository,
        private readonly PutaranServiceInterface $putaranService,
        private readonly SyaratSimulasiServiceInterface $syaratService,
        private readonly SoalPickerServiceInterface $soalPicker,
        private readonly SimulasiRepositoryInterface $simulasiRepository,
        private readonly AturanServiceInterface $aturanService,
        private readonly TingkatSeleksiRepositoryInterface $tingkatRepository,
        private readonly PerhitunganClientInterface $perhitungan,
        private readonly KelulusanServiceInterface $kelulusanService,
    ) {}

    /**
     * Daftar simulasi satu tingkat beserta sisa kuota percobaannya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function daftar(User $user, int $tingkatId): array
    {
        $tingkat = $this->tingkatModel($tingkatId);
        $status = $this->putaranService->status($user, $tingkat);

        if (! $status->tingkatTerbuka) {
            throw new TingkatTerkunciException("Tingkat {$tingkat->nama_tingkat} belum terbuka.");
        }

        $daftar = [];

        foreach ($this->simulasiRepository->untukTingkat($tingkatId) as $simulasi) {
            $daftar[] = [
                'id' => (int) $simulasi->id,
                'nama_simulasi' => (string) $simulasi->nama_simulasi,
                'jumlah_soal' => (int) $simulasi->jumlah_soal,
                'durasi_menit' => (int) $simulasi->durasi_menit,
                'is_aktif' => (bool) $simulasi->is_aktif,
                // Kuota berlaku per putaran, bukan per simulasi.
                'sisa_kuota' => $status->sisaKuotaSimulasi,
            ];
        }

        return $daftar;
    }

    /**
     * Mulai percobaan, atau lanjutkan yang sedang berjalan. Urutan cek 1–9
     * sesuai dokumen, di dalam satu transaksi yang mengunci baris pretest
     * putaran aktif.
     *
     * Satu-satunya langkah di luar transaksi itu adalah menutup percobaan
     * yang batas waktunya sudah lewat (bagian dari langkah 4). Penutupan
     * memanggil Python dan menyimpan nilai beserta kelulusan; bila dijalankan
     * di dalam transaksi mulai, penolakan sesudahnya akan ikut membatalkan
     * nilai yang baru disimpan.
     */
    public function mulai(User $user, Simulasi $simulasi): SimulasiDimulai
    {
        $tingkat = $simulasi->tingkat;

        // 1. Simulasi harus aktif dan tingkatnya terbuka.
        if (! $simulasi->is_aktif) {
            throw (new ModelNotFoundException)->setModel(Simulasi::class, [$simulasi->id]);
        }

        $statusAwal = $this->putaranService->status($user, $tingkat);

        if (! $statusAwal->tingkatTerbuka) {
            throw new TingkatTerkunciException("Tingkat {$tingkat->nama_tingkat} belum terbuka.");
        }

        $this->tutupBilaKedaluwarsa($user, (int) $tingkat->id);

        return DB::transaction(function () use ($user, $simulasi, $tingkat): SimulasiDimulai {
            // 2. Kunci baris pretest putaran aktif. Tanpa putaran aktif,
            // syarat simulasi pasti belum terpenuhi.
            $putaranAktif = $this->pretestRepository->putaranAktifTerbaru($user->id, (int) $tingkat->id);

            if (! $putaranAktif instanceof Pretest) {
                throw new SyaratSimulasiBelumTerpenuhiException('Simulasi membutuhkan pre-test yang berjalan penuh.');
            }

            $terkunci = $this->pretestRepository->findUntukUpdate($putaranAktif->id);

            if (! $terkunci instanceof Pretest) {
                throw new SyaratSimulasiBelumTerpenuhiException('Putaran aktif tidak ditemukan.');
            }

            // 3. Sudah lulus tingkat itu.
            if ($this->kenaikanRepository->adaLulus($user->id, (int) $tingkat->id)) {
                throw new SudahLulusException("Tingkat {$tingkat->nama_tingkat} sudah lulus.");
            }

            // 4. Ada simulasi berjalan: kembalikan, tolak, atau tutup dulu.
            $berjalan = $this->hasilRepository->berjalan($user->id, (int) $terkunci->id);

            if ($berjalan instanceof HasilSimulasi) {
                return $this->lanjutan($berjalan);
            }

            $aturan = $this->aturanService->untukTingkat((int) $tingkat->id);

            $terpakai = $this->hasilRepository->jumlahPercobaan((int) $terkunci->id);

            // 5. Kuota percobaan habis.
            if ($terpakai >= (int) $aturan->simulasiMaksPercobaan) {
                throw new KuotaSimulasiHabisException('Semua percobaan simulasi sudah dipakai.');
            }

            // 6. Syarat simulasi belum terpenuhi: dibalas kode bisnisnya
            // beserta rincian per materi wajib, dalam bentuk JSON di detail
            // seperti BANK_SOAL_TIDAK_CUKUP.
            $syarat = $this->syaratService->periksa($user, $tingkat);

            if (! $syarat->terpenuhi) {
                $belum = count(array_filter(
                    $syarat->rincian,
                    fn ($baris): bool => ! ($baris['selesai'] ?? false)
                        || ($baris['nilai_latihan'] ?? 0) < ($baris['batas'] ?? 0),
                ));

                throw new SyaratSimulasiBelumTerpenuhiException(
                    detail: (string) json_encode([
                        'pesan' => "{$belum} dari ".count($syarat->rincian).' materi wajib belum memenuhi syarat.',
                        'rincian' => $syarat->rincian,
                    ]),
                );
            }

            // 7. Daftar dihindari = semua soal dari percobaan lain putaran ini.
            $terpilih = $this->soalPicker->pilih(new PermintaanSoal(
                tingkatId: (int) $tingkat->id,
                peruntukan: Peruntukan::Simulasi,
                jumlahSoal: (int) $simulasi->jumlah_soal,
                persenLevel: $aturan->persenLevelSimulasi,
                soalDihindari: $this->jawabanRepository->soalIdsTerpakaiPutaran((int) $terkunci->id),
            ));

            // 8. Buat percobaan dan baris jawaban, lalu tutup transaksi.
            $mulaiPada = now();

            $hasil = $this->hasilRepository->create([
                'user_id' => $user->id,
                'simulasi_id' => $simulasi->id,
                'pretest_id' => $terkunci->id,
                'mulai_pada' => $mulaiPada,
                'batas_pada' => $mulaiPada->copy()->addMinutes((int) $simulasi->durasi_menit),
            ]);

            $this->jawabanRepository->buatBanyak($hasil->id, $terpilih->butir);

            return $this->balasan($hasil, baru: true);
        });
    }

    public function ringkasan(User $user, int $hasilId): SimulasiDimulai
    {
        return $this->balasan($this->hasilMilik($user, $hasilId), baru: false);
    }

    public function simpanJawaban(User $user, int $hasilId, int $soalId, ?string $jawaban): void
    {
        $hasil = $this->hasilMilik($user, $hasilId);

        // Waktu server melewati batas ditambah toleransi.
        if (now()->greaterThan($hasil->batas_pada->copy()->addSeconds(self::TOLERANSI_DETIK))) {
            throw new WaktuHabisException('Waktu pengerjaan simulasi sudah habis.');
        }

        if ($hasil->disubmit_pada !== null) {
            throw new PengerjaanSudahDisubmitException('Jawaban sudah dikunci saat simulasi disubmit.');
        }

        $baris = $this->jawabanRepository->findJawaban($hasil->id, $soalId);

        if (! $baris instanceof HasilSimulasiJawaban) {
            throw ValidationException::withMessages([
                'soal_id' => ['Soal ini bukan bagian dari simulasi tersebut.'],
            ]);
        }

        $baris->forceFill(['jawaban_user' => $jawaban])->save();
    }

    public function review(User $user, int $hasilId): HasilSimulasi
    {
        $hasil = $this->hasilMilik($user, $hasilId);

        if ($hasil->selesai_pada === null) {
            throw new SimulasiBelumDinilaiException('Review hanya tersedia setelah simulasi dinilai.');
        }

        return $hasil;
    }

    public function submit(User $user, int $hasilId): HasilSimulasi
    {
        $hasil = $this->hasilMilik($user, $hasilId);

        return $this->submitInternal($hasil);
    }

    /**
     * Alur submit yang sama untuk request siswa, command penutup, dan retry
     * job: kunci, panggil Python di luar transaksi, simpan + nilai dalam
     * transaksi kedua.
     */
    private function submitInternal(HasilSimulasi $hasil): HasilSimulasi
    {
        // Transaksi 1: kunci baris. Bila selesai, kembalikan yang ada.
        $terkunci = DB::transaction(function () use ($hasil): HasilSimulasi {
            $baris = $this->hasilRepository->findUntukUpdate($hasil->id);

            if (! $baris instanceof HasilSimulasi) {
                throw (new ModelNotFoundException)->setModel(HasilSimulasi::class, [$hasil->id]);
            }

            if ($baris->selesai_pada === null && $baris->disubmit_pada === null) {
                $baris->forceFill(['disubmit_pada' => now()])->save();
            }

            return $baris;
        });

        if ($terkunci->selesai_pada !== null) {
            return $terkunci->load('jawaban');
        }

        try {
            $this->selesaikanPenilaian($terkunci->id);
        } catch (PerhitunganTidakTersediaException $exception) {
            NilaiUlangJob::dispatch(JenisPengerjaan::Simulasi, $terkunci->id);

            throw new PerhitunganTidakTersediaException(
                "Penilaian simulasi #{$terkunci->id} gagal dan dijadwalkan untuk dicoba lagi.",
            );
        }

        return $terkunci->fresh() ?? $terkunci;
    }

    /**
     * Susun payload, panggil Python di luar transaksi, simpan hasil beserta
     * keputusan kelulusan dalam transaksi kedua. Idempoten untuk job:
     * pemeriksaan di awal hanya menghemat panggilan Python, penjaga yang
     * sebenarnya ada di transaksi kedua, di bawah kunci baris.
     */
    public function selesaikanPenilaian(int $id): void
    {
        $hasil = $this->hasilRepository->find($id);

        if (! $hasil instanceof HasilSimulasi || $hasil->selesai_pada !== null) {
            return;
        }

        $jawaban = $this->jawabanRepository->untukHasil($id);

        $balasan = $this->perhitungan->hitungPenilaian($this->payloadSoal($jawaban));

        $statusBenar = collect($balasan['jawaban'])->keyBy('soal_id');
        $benar = 0;
        $salah = 0;

        foreach ($jawaban as $baris) {
            $status = $statusBenar->get((int) $baris->soal_id);

            if ((bool) ($status['status_benar'] ?? false)) {
                $benar++;
            } else {
                $salah++;
            }
        }

        DB::transaction(function () use ($id, $jawaban, $balasan, $statusBenar, $benar, $salah): void {
            // Job dan submit ulang bisa sampai di sini bersamaan. Yang kalah
            // menunggu kunci, lalu melihat selesai_pada sudah terisi, sehingga
            // kelulusan tidak pernah dievaluasi dua kali.
            $hasil = $this->hasilRepository->findUntukUpdate($id);

            if (! $hasil instanceof HasilSimulasi || $hasil->selesai_pada !== null) {
                return;
            }

            foreach ($jawaban as $baris) {
                $status = $statusBenar->get((int) $baris->soal_id);

                $baris->forceFill([
                    'status_benar' => (bool) ($status['status_benar'] ?? false),
                ])->save();
            }

            $hasil->forceFill([
                'nilai' => $balasan['nilai'],
                'jumlah_benar' => $benar,
                'jumlah_salah' => $salah,
                'selesai_pada' => now(),
            ])->save();

            // Kelulusan ikut dijalankan di sini, sehingga penilaian yang
            // berasal dari job ulang tetap menulis kenaikan tingkat.
            $this->kelulusanService->menilai($hasil);
        });
    }

    /**
     * Tutup semua percobaan kedaluwarsa atas nama sistem. Mengembalikan
     * jumlah yang berhasil ditutup.
     */
    public function tutupKedaluwarsa(): int
    {
        $ditutup = 0;

        foreach ($this->hasilRepository->kedaluwarsa(self::TOLERANSI_DETIK) as $hasil) {
            try {
                $this->submitInternal($hasil);
                $ditutup++;
            } catch (PerhitunganTidakTersediaException $exception) {
                // Sudah dikirim ke job oleh submitInternal; lanjut ke
                // percobaan berikutnya supaya satu kegagalan tidak menahan
                // seluruh jadwal.
                continue;
            } catch (Throwable $exception) {
                // Kegagalan lain (misalnya layanan hitung salah konfigurasi)
                // juga tidak boleh menahan percobaan berikutnya. Baris ini
                // sudah terkunci oleh disubmit_pada, jadi dicatat untuk admin.
                Log::error('Penutupan simulasi kedaluwarsa gagal.', [
                    'hasil_simulasi_id' => $hasil->id,
                    'exception' => $exception::class,
                    'pesan' => $exception->getMessage(),
                ]);

                continue;
            }
        }

        return $ditutup;
    }

    /**
     * Bagian dari langkah 4 mulai: percobaan yang belum disubmit tetapi
     * batasnya sudah lewat ditutup lewat alur submit yang sama, sebelum
     * transaksi mulai dibuka. Bila Python gagal, submitInternal sudah
     * mengirim job dan 503-nya diteruskan ke siswa.
     */
    private function tutupBilaKedaluwarsa(User $user, int $tingkatId): void
    {
        $putaranAktif = $this->pretestRepository->putaranAktifTerbaru($user->id, $tingkatId);

        if (! $putaranAktif instanceof Pretest) {
            return;
        }

        $berjalan = $this->hasilRepository->berjalan($user->id, (int) $putaranAktif->id);

        if ($berjalan instanceof HasilSimulasi && $berjalan->disubmit_pada === null && $this->lewatBatas($berjalan)) {
            $this->submitInternal($berjalan);
        }
    }

    /**
     * Percobaan berjalan yang ditemukan di langkah 4: bila belum disubmit
     * dan masih dalam batas, kembalikan; selain itu tolak tanpa menulis apa
     * pun. Method ini berjalan di dalam transaksi mulai, jadi tidak boleh
     * menilai: percobaan kedaluwarsa sudah ditutup tutupBilaKedaluwarsa
     * sebelum transaksi dibuka.
     */
    private function lanjutan(HasilSimulasi $berjalan): SimulasiDimulai
    {
        if ($berjalan->disubmit_pada === null && ! $this->lewatBatas($berjalan)) {
            return $this->balasan($berjalan, baru: false);
        }

        // Sudah disubmit tetapi belum bernilai, atau baru saja melewati
        // batas di sela penutupan dan transaksi ini.
        throw new SimulasiBelumDinilaiException('Percobaan ini sedang dinilai; tunggu sebentar.');
    }

    private function lewatBatas(HasilSimulasi $hasil): bool
    {
        return now()->greaterThan($hasil->batas_pada->copy()->addSeconds(self::TOLERANSI_DETIK));
    }

    /**
     * @param  Collection<int, HasilSimulasiJawaban>  $jawaban
     * @return list<array{soal_id: int, tipe_soal: string, bobot: int, jawaban_user: ?string, kunci_jawaban: string}>
     */
    private function payloadSoal(Collection $jawaban): array
    {
        $payload = [];

        foreach ($jawaban as $baris) {
            /** @var Soal $soal */
            $soal = $baris->soal;

            $payload[] = [
                'soal_id' => (int) $soal->id,
                'tipe_soal' => $soal->tipe_soal->value,
                'bobot' => (int) $baris->bobot,
                'jawaban_user' => $baris->jawaban_user,
                'kunci_jawaban' => (string) $soal->kunci_jawaban,
            ];
        }

        return $payload;
    }

    private function hasilMilik(User $user, int $hasilId): HasilSimulasi
    {
        $hasil = $this->hasilRepository->findMilik($hasilId, $user->id);

        if (! $hasil instanceof HasilSimulasi) {
            throw (new ModelNotFoundException)->setModel(HasilSimulasi::class, [$hasilId]);
        }

        return $hasil;
    }

    private function tingkatModel(int $tingkatId): TingkatSeleksi
    {
        $tingkat = $this->tingkatRepository->find($tingkatId);

        if (! $tingkat instanceof TingkatSeleksi) {
            throw (new ModelNotFoundException)->setModel(TingkatSeleksi::class, [$tingkatId]);
        }

        return $tingkat;
    }

    private function balasan(HasilSimulasi $hasil, bool $baru): SimulasiDimulai
    {
        $jawaban = $this->jawabanRepository->untukHasil($hasil->id);

        $peta = [];

        foreach ($jawaban as $baris) {
            $peta[(int) $baris->soal_id] = $baris->soal;
        }

        $hasil->setRelation('jawaban', $jawaban);
        $hasil->setRelation('soal', new Collection(array_values($peta)));

        $hasil->loadMissing(['simulasi.tingkat']);

        return new SimulasiDimulai(
            hasil: $hasil,
            soal: new Collection(array_values($peta)),
            baru: $baru,
        );
    }
}
