<?php

namespace Database\Factories;

use App\Enums\StatusProgress;
use App\Models\Materi;
use App\Models\ProgressBelajar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgressBelajar>
 */
class ProgressBelajarFactory extends Factory
{
    protected $model = ProgressBelajar::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'materi_id' => Materi::factory(),
            'status' => StatusProgress::Belajar,
            'persentase' => 0,
            'tanggal_selesai' => null,
        ];
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusProgress::Selesai,
            'persentase' => 100,
            'tanggal_selesai' => now(),
        ]);
    }
}
