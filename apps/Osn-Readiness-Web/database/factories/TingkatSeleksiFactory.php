<?php

namespace Database\Factories;

use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TingkatSeleksi>
 */
class TingkatSeleksiFactory extends Factory
{
    protected $model = TingkatSeleksi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_tingkat' => fake()->unique()->state(),
            'deskripsi' => fake()->sentence(),
            'urutan' => fake()->unique()->numberBetween(1, 100),
        ];
    }

    public function kabupaten(): static
    {
        return $this->state(fn (array $attributes): array => [
            'nama_tingkat' => 'Kabupaten/Kota',
            'urutan' => 1,
        ]);
    }

    public function provinsi(): static
    {
        return $this->state(fn (array $attributes): array => [
            'nama_tingkat' => 'Provinsi',
            'urutan' => 2,
        ]);
    }
}
