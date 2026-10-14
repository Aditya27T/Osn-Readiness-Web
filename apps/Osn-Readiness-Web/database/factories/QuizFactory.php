<?php

namespace Database\Factories;

use App\Models\Materi;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'materi_id' => Materi::factory(),
            'nama_quiz' => 'Latihan '.fake()->word(),
            'deskripsi' => fake()->sentence(),
            // Kolom ini punya check constraint minimal 10.
            'jumlah_soal' => 10,
        ];
    }
}
