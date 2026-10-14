<?php

namespace Database\Factories;

use App\Models\Pretest;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pretest>
 */
class PretestFactory extends Factory
{
    protected $model = Pretest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tingkat_id' => TingkatSeleksi::factory(),
            'disubmit_pada' => null,
            'nilai' => null,
            'selesai_pada' => null,
        ];
    }

    /**
     * Pre-test yang belum selesai: ini yang menjadi putaran aktif.
     */
    public function berjalan(): static
    {
        return $this->state(fn (array $attributes): array => [
            'disubmit_pada' => null,
            'nilai' => null,
            'selesai_pada' => null,
        ]);
    }

    /**
     * Pre-test yang sudah selesai, jadi bukan lagi putaran aktif.
     */
    public function selesai(float $nilai = 75): static
    {
        return $this->state(fn (array $attributes): array => [
            'disubmit_pada' => now(),
            'nilai' => $nilai,
            'selesai_pada' => now(),
        ]);
    }

    /**
     * Jawaban terkunci tapi Python belum memberi nilai.
     */
    public function terkunci(): static
    {
        return $this->state(fn (array $attributes): array => [
            'disubmit_pada' => now(),
            'nilai' => null,
            'selesai_pada' => null,
        ]);
    }
}
