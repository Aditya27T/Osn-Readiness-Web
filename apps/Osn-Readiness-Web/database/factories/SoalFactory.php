<?php

namespace Database\Factories;

use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Enums\TipeSoal;
use App\Models\Materi;
use App\Models\Pembahasan;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Soal>
 */
class SoalFactory extends Factory
{
    protected $model = Soal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_sumber' => null,
            'materi_id' => Materi::factory(),
            // Diisi dari materi pada configure(), karena soal harus setingkat dengan materinya.
            'tingkat_id' => null,
            'konteks_id' => null,
            'level' => fake()->randomElement(Level::cases()),
            'peruntukan' => fake()->randomElement(Peruntukan::cases()),
            'tipe_soal' => TipeSoal::PilihanGanda,
            'pertanyaan' => fake()->sentence(4),
            'pilihan_jawaban' => [
                'A' => fake()->word(),
                'B' => fake()->word(),
                'C' => fake()->word(),
                'D' => fake()->word(),
            ],
            'kunci_jawaban' => 'A',
            'gambar' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Soal $soal): void {
            if ($soal->materi_id !== null && $soal->tingkat_id === null) {
                $soal->tingkat_id = Materi::findOrFail($soal->materi_id)->tingkat_id;
            }
        });
    }

    public function untukMateri(Materi $materi): static
    {
        return $this->state(fn (array $attributes): array => [
            'tingkat_id' => $materi->tingkat_id,
            'materi_id' => $materi->id,
        ]);
    }

    public function level(Level $level): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => $level,
        ]);
    }

    public function peruntukan(Peruntukan $peruntukan): static
    {
        return $this->state(fn (array $attributes): array => [
            'peruntukan' => $peruntukan,
        ]);
    }

    public function pilihanGanda(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tipe_soal' => TipeSoal::PilihanGanda,
        ]);
    }

    public function isian(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tipe_soal' => TipeSoal::Isian,
            'pilihan_jawaban' => null,
            'kunci_jawaban' => fake()->word(),
        ]);
    }

    /**
     * Soal dengan satu baris pembahasan, untuk keperluan review.
     */
    public function denganPembahasan(): static
    {
        return $this->afterCreating(function (Soal $soal): void {
            Pembahasan::factory()->for($soal)->create();
        });
    }
}
