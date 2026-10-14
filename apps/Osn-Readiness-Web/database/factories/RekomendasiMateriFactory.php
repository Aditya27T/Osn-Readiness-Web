<?php

namespace Database\Factories;

use App\Models\Materi;
use App\Models\Pretest;
use App\Models\RekomendasiMateri;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RekomendasiMateri>
 */
class RekomendasiMateriFactory extends Factory
{
    protected $model = RekomendasiMateri::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pretest_id' => Pretest::factory(),
            'user_id' => User::factory(),
            'materi_id' => Materi::factory(),
            'prioritas' => 1,
        ];
    }
}
