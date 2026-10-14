<?php

namespace Database\Seeders;

use App\Models\AturanPemetaan;
use App\Models\TingkatSeleksi;
use Illuminate\Database\Seeder;

/**
 * 16 parameter per tingkat, total 32 baris.
 *
 * Memakai firstOrCreate supaya aman dijalankan ulang tanpa menimpa angka yang
 * sudah diubah admin: baris yang sudah ada tidak diubah nilainya.
 */
class AturanPemetaanSeeder extends Seeder
{
    /**
     * Ketentuan dasar per tingkat, diindeks dengan urutan tingkat.
     *
     * @var array<int, array<string, string>>
     */
    private const KETENTUAN = [
        1 => [
            'bobot_mudah' => '1',
            'bobot_sedang' => '2',
            'bobot_sulit' => '3',
            'pretest_jumlah_soal' => '30',
            'pretest_persen_mudah' => '50',
            'pretest_persen_sedang' => '30',
            'pretest_persen_sulit' => '20',
            'pretest_min_soal_per_materi' => '2',
            'jumlah_materi_wajib' => '3',
            'latihan_min_soal' => '10',
            'latihan_min_nilai' => '50',
            'simulasi_persen_mudah' => '30',
            'simulasi_persen_sedang' => '40',
            'simulasi_persen_sulit' => '30',
            'simulasi_maks_percobaan' => '3',
            'passing_grade' => '70',
        ],
        2 => [
            'bobot_mudah' => '1',
            'bobot_sedang' => '2',
            'bobot_sulit' => '3',
            'pretest_jumlah_soal' => '30',
            'pretest_persen_mudah' => '50',
            'pretest_persen_sedang' => '30',
            'pretest_persen_sulit' => '20',
            'pretest_min_soal_per_materi' => '2',
            'jumlah_materi_wajib' => '3',
            'latihan_min_soal' => '10',
            'latihan_min_nilai' => '50',
            'simulasi_persen_mudah' => '30',
            'simulasi_persen_sedang' => '40',
            'simulasi_persen_sulit' => '30',
            'simulasi_maks_percobaan' => '3',
            'passing_grade' => '80',
        ],
    ];

    public function run(): void
    {
        foreach (self::KETENTUAN as $urutan => $ketentuan) {
            $tingkat = TingkatSeleksi::where('urutan', $urutan)->first();

            if ($tingkat === null) {
                $this->command?->warn("Tingkat urutan {$urutan} belum ada, aturannya dilewati.");

                continue;
            }

            foreach ($ketentuan as $parameter => $nilai) {
                AturanPemetaan::firstOrCreate(
                    ['tingkat_id' => $tingkat->id, 'parameter' => $parameter],
                    ['ketentuan' => $nilai],
                );
            }
        }
    }
}
