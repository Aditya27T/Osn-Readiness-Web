<?php

namespace Database\Factories;

use App\Models\Simulasi;
use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Simulasi>
 */
class SimulasiFactory extends Factory
{
    protected $model = Simulasi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tingkat_id' => TingkatSeleksi::factory(),
            'nama_simulasi' => fake()->sentence(3),
            'deskripsi' => fake()->sentence(),
            'jumlah_soal' => 30,
            'durasi_menit' => 120,
            'is_aktif' => true,
        ];
    }

    public function tidakAktif(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_aktif' => false,
        ]);
    }
}
