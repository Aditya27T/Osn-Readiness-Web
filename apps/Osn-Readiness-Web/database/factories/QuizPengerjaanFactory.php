<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizPengerjaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizPengerjaan>
 */
class QuizPengerjaanFactory extends Factory
{
    protected $model = QuizPengerjaan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'disubmit_pada' => null,
            'nilai' => null,
            'selesai_pada' => null,
        ];
    }

    public function berjalan(): static
    {
        return $this->state(fn (array $attributes): array => [
            'disubmit_pada' => null,
            'nilai' => null,
            'selesai_pada' => null,
        ]);
    }

    public function selesai(float $nilai = 80): static
    {
        return $this->state(fn (array $attributes): array => [
            'disubmit_pada' => now(),
            'nilai' => $nilai,
            'selesai_pada' => now(),
        ]);
    }

    public function terkunci(): static
    {
        return $this->state(fn (array $attributes): array => [
            'disubmit_pada' => now(),
            'nilai' => null,
            'selesai_pada' => null,
        ]);
    }
}
