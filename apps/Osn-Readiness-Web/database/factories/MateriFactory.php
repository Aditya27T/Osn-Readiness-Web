<?php

namespace Database\Factories;

use App\Models\Kompetensi;
use App\Models\Materi;
use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materi>
 */
class MateriFactory extends Factory
{
    protected $model = Materi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_sumber' => null,
            'tingkat_id' => TingkatSeleksi::factory(),
            'kompetensi_id' => Kompetensi::factory(),
            'urutan' => fake()->unique()->numberBetween(1, 1000),
            'judul' => fake()->sentence(3),
            'deskripsi' => fake()->sentence(),
            'isi_materi' => fake()->paragraphs(3, true),
            'file_materi' => null,
        ];
    }

    /**
     * Materi dan kompetensi harus dari tingkat yang sama.
     */
    public function untukTingkat(TingkatSeleksi $tingkat): static
    {
        return $this->state(fn (array $attributes): array => [
            'tingkat_id' => $tingkat->id,
            'kompetensi_id' => Kompetensi::factory()->for($tingkat),
        ]);
    }
}
