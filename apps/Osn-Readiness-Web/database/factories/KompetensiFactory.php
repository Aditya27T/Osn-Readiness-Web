<?php

namespace Database\Factories;

use App\Models\Kompetensi;
use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kompetensi>
 */
class KompetensiFactory extends Factory
{
    protected $model = Kompetensi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tingkat_id' => TingkatSeleksi::factory(),
            'nama_kompetensi' => fake()->unique()->words(3, asText: true),
            'deskripsi' => fake()->sentence(),
        ];
    }
}
