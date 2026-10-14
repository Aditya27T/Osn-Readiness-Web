<?php

namespace Database\Factories;

use App\Models\Materi;
use App\Models\PemetaanMateri;
use App\Models\Pretest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PemetaanMateri>
 */
class PemetaanMateriFactory extends Factory
{
    protected $model = PemetaanMateri::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pretest_id' => Pretest::factory(),
            'user_id' => User::factory(),
            'materi_id' => Materi::factory(),
            'jumlah_soal' => 10,
            'jumlah_benar' => 5,
            'poin_didapat' => 8,
            'poin_maksimal' => 16,
            'persentase' => 50,
            'peringkat' => 1,
        ];
    }
}
