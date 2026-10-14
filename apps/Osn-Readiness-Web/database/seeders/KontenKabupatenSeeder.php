<?php

namespace Database\Seeders;

use App\Contracts\Services\ImporKontenServiceInterface;
use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Models\AturanPemetaan;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use Illuminate\Database\Seeder;

/**
 * Bank soal kabupaten nyata untuk pengembangan lokal.
 *
 * Menggantikan KontenContohSeeder yang memakai SoalFactory. Sumbernya folder
 * impor dari repo data-analytics (lihat config('osn.konten_kabupaten_folder')),
 * yang strukturnya sudah persis format ImporKontenService: materi/*.md,
 * soal_*.json, dan gambar/.
 *
 * Impor memakai upsert lewat id_sumber, jadi seeder ini aman dijalankan
 * ulang: baris yang sudah ada diperbarui, bukan diduplikasi.
 *
 * Keterbatasan bank kabupaten yang perlu diketahui:
 *
 * - Level pretest pada sumber hanya punya `mudah` dan `sulit`; tidak ada satu
 *   pun soal pretest berlevel `sedang` di kelima materi. Aturan pemetaan bawaan
 *   meminta kuota 50/30/20, jadi pretest akan selalu gagal. Karena itu seeder
 *   menyelaraskan kuota level yang memang kosong (lihat selaraskanKuotaPretest).
 *   Label di data-analytics tidak diubah: label itu disusun manual mengikuti
 *   silabus, jadi memalsukan level demi lolos kuotanya bukan perbaikan.
 * - Jumlah pretest kabupaten juga tipis: beberapa materi hanya punya belasan
 *   soal pretest, jadi beberapa putaran pretest tidak mungkin terpakai.
 *
 * Aturan pemetaan hanya diubah untuk level yang terbukti kosong di bank
 * yang diimpor. Sisanya mengikuti AturanPemetaanSeeder apa adanya.
 */
class KontenKabupatenSeeder extends Seeder
{
    /**
     * Jumlah soal per latihan. Harus ≥10 karena ada CHECK di tabel quiz.
     */
    private const SOAL_PER_LATIHAN = 10;

    public function run(ImporKontenServiceInterface $imporKonten): void
    {
        $folder = (string) config('osn.konten_kabupaten_folder');

        if ($folder === '' || ! is_dir($folder)) {
            $this->command?->warn(
                'KontenKabupatenSeeder dilewati: folder impor belum diatur. '
                .'Isi KONTEN_KABUPATEN_FOLDER di .env untuk memakai bank soal kabupaten.',
            );

            return;
        }

        $laporan = $imporKonten->impor($folder, false, 'kabupaten');

        $this->command?->info(sprintf(
            'Impor kabupaten: %d materi masuk, %d diperbarui; %d soal masuk, %d diperbarui.',
            $laporan['materi']['masuk'],
            $laporan['materi']['diperbarui'],
            $laporan['soal']['masuk'],
            $laporan['soal']['diperbarui'],
        ));

        $ditolak = count($laporan['soal']['ditolak']);

        if ($ditolak > 0) {
            $this->command?->warn("{$ditolak} soal kabupaten ditolak saat impor.");
        }

        $kabupaten = TingkatSeleksi::query()->where('urutan', 1)->first();

        if ($kabupaten instanceof TingkatSeleksi) {
            $this->selaraskanKuotaPretest($kabupaten);
            $this->seedLatihan();
            $this->seedSimulasi($kabupaten);
            $this->peringatanKecukupan($kabupaten);
        }
    }

    /**
     * Satu latihan per materi kabupaten. Imporir tidak membuat quiz, jadi ini
     * dibuat di sini supaya alur belajar punya latihan untuk dikerjakan.
     */
    /**
     * Selaraskan kuota level pretest dengan bank yang benar-benar diimpor.
     *
     * Bank kabupaten tidak punya soal pretest berlevel sedang sama sekali,
     * sedangkan AturanPemetaan default meminta kuota 30%. Tanpa penyelarasan
     * ini pretest selalu gagal dengan BankSoalTidakCukupException.
     *
     * Hanya level yang benar-benar kosong di bank yang dinolkan. Level yang
     * punya soal dibiarkan apa adanya supaya komposisi difficulty tetap
     * mengikuti data, bukan hasil pembagian rata.
     */
    private function selaraskanKuotaPretest(TingkatSeleksi $kabupaten): void
    {
        foreach (Level::cases() as $level) {
            $ada = Soal::query()
                ->where('tingkat_id', $kabupaten->id)
                ->where('peruntukan', Peruntukan::Pretest->value)
                ->where('level', $level->value)
                ->count();

            if ($ada > 0) {
                continue;
            }

            $parameter = 'pretest_persen_'.$level->value;

            AturanPemetaan::query()
                ->where('tingkat_id', $kabupaten->id)
                ->where('parameter', $parameter)
                ->update(['ketentuan' => '0']);

            $this->command?->warn(
                "Bank pretest tidak punya soal level {$level->value}; "
                ."{$parameter} dinolkan supaya kuota tidak menuntut soal yang tidak ada.",
            );
        }
    }

    private function seedLatihan(): void
    {
        $materi = Materi::query()
            ->whereHas('tingkat', fn ($query) => $query->where('urutan', 1))
            ->get();

        foreach ($materi as $satu) {
            Quiz::firstOrCreate(
                ['materi_id' => $satu->id],
                [
                    'nama_quiz' => 'Latihan '.$satu->judul,
                    'deskripsi' => 'Latihan kabupaten untuk '.$satu->judul.'.',
                    'jumlah_soal' => self::SOAL_PER_LATIHAN,
                ],
            );
        }
    }

    private function seedSimulasi(TingkatSeleksi $kabupaten): void
    {
        Simulasi::firstOrCreate(
            [
                'tingkat_id' => $kabupaten->id,
                'nama_simulasi' => 'Simulasi '.$kabupaten->nama_tingkat,
            ],
            [
                'deskripsi' => 'Simulasi kabupaten dari bank soal OSN kabupaten.',
                'jumlah_soal' => 30,
                'durasi_menit' => 120,
                'is_aktif' => true,
            ],
        );
    }

    /**
     * Cetak hitungan bank kabupaten per materi dan peruntukan supaya kekurangan
     * yang diketahui sejak awal terlihat di log seeder, bukan Baru saat siswa
     * gagal memulai pretest.
     */
    private function peringatanKecukupan(TingkatSeleksi $kabupaten): void
    {
        $baris = Soal::query()
            ->join('materi', 'materi.id', '=', 'soal.materi_id')
            ->where('soal.tingkat_id', $kabupaten->id)
            ->selectRaw('materi.judul, soal.peruntukan, soal.level, count(*) as jumlah')
            ->groupBy('materi.judul', 'soal.peruntukan', 'soal.level')
            ->get();

        if ($baris->isEmpty()) {
            return;
        }

        $this->command?->info('Bank soal kabupaten per materi:');

        foreach ($baris->groupBy('judul') as $judul => $perMateri) {
            $rincian = [];

            foreach ($perMateri as $satu) {
                // Kolom soal casts ke enum, jadi nilai-mentahnya dibungkus objek.
                $peruntukan = $satu->peruntukan instanceof Peruntukan
                    ? $satu->peruntukan->value
                    : (string) $satu->peruntukan;

                $level = $satu->level instanceof Level
                    ? $satu->level->value
                    : (string) $satu->level;

                $rincian[] = sprintf('%s/%s=%d', $peruntukan, $level, (int) $satu->jumlah);
            }

            $this->command?->line('  '.$judul.': '.implode(', ', $rincian));
        }

        $tanpaSedang = Soal::query()
            ->where('tingkat_id', $kabupaten->id)
            ->where('peruntukan', Peruntukan::Pretest->value)
            ->where('level', Level::Sedang->value)
            ->count();

        if ($tanpaSedang === 0) {
            $this->command?->warn(
                'Keterbatasan bank sumber: tidak ada soal pretest berlevel sedang untuk '
                .'kabupaten, jadi komposisi difficulty pretest lokal hanya mudah dan '
                .'sulit. Kuota sudah diselaraskan di atas. Bank produksi perlu '
                .'dilabeli ulang di data-analytics bila komposisi ini tidak diinginkan.',
            );
        }
    }
}
