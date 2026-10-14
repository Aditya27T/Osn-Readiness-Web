<?php

namespace Database\Factories;

use App\Models\KonteksSoal;
use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KonteksSoal>
 */
class KonteksSoalFactory extends Factory
{
    protected $model = KonteksSoal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tingkat_id' => TingkatSeleksi::factory(),
            'judul' => fake()->sentence(4),
            'isi_konteks' => fake()->paragraphs(2, true),
            'gambar' => null,
        ];
    }

    public function untukTingkat(TingkatSeleksi $tingkat): static
    {
        return $this->state(fn (array $attributes): array => [
            'tingkat_id' => $tingkat->id,
        ]);
    }
}
