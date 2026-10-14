<?php

namespace App\Observers;

use App\Models\AturanPemetaan;
use App\Models\TingkatSeleksi;
use Illuminate\Support\Facades\DB;

class TingkatSeleksiObserver
{
    /** Karena bila satu tingkat baru dibuat, langsung lengkap dengan aturan bawaannya. */
    private const DEFAULT = [
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
    ];

    /**
     * Handle the TingkatSeleksi "created" event.
     */
    public function created(TingkatSeleksi $tingkatSeleksi): void
    {
        // Skip during seeding - let seeders handle aturan creation
        if ($this->isSeeding()) {
            return;
        }

        // Auto-create default aturan for new tingkat if not already exists
        DB::transaction(function () use ($tingkatSeleksi): void {
            if ($tingkatSeleksi->aturanPemetaan()->exists()) {
                return;
            }

            foreach (self::DEFAULT as $parameter => $ketentuan) {
                AturanPemetaan::create([
                    'tingkat_id' => $tingkatSeleksi->id,
                    'parameter' => $parameter,
                    'ketentuan' => $ketentuan,
                ]);
            }
        });
    }

    /**
     * Check if we're currently seeding the database.
     */
    private function isSeeding(): bool
    {
        // During seeding, check if AturanPemetaanSeeder is in the call stack
        return in_array('Database\Seeders\AturanPemetaanSeeder',
            array_map(fn ($trace) => $trace['class'] ?? '', debug_backtrace()),
            true);
    }
}
