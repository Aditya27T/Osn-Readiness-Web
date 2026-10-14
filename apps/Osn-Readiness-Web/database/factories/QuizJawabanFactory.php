<?php

namespace Database\Factories;

use App\Models\QuizJawaban;
use App\Models\QuizPengerjaan;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizJawaban>
 */
class QuizJawabanFactory extends Factory
{
    protected $model = QuizJawaban::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pengerjaan_id' => QuizPengerjaan::factory(),
            'soal_id' => Soal::factory(),
            'urutan' => 1,
            'bobot' => 1,
            'jawaban_user' => null,
            'status_benar' => null,
        ];
    }

    public function untukSoal(Soal $soal, int $urutan = 1, int $bobot = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'soal_id' => $soal->id,
            'urutan' => $urutan,
            'bobot' => $bobot,
        ]);
    }

    public function sudahDijawab(bool $benar = true): static
    {
        return $this->state(fn (array $attributes): array => [
            'jawaban_user' => 'A',
            'status_benar' => $benar,
        ]);
    }
}
