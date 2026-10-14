<?php

namespace Database\Factories;

use App\Models\Pembahasan;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembahasan>
 */
class PembahasanFactory extends Factory
{
    protected $model = Pembahasan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'soal_id' => Soal::factory(),
            'isi_pembahasan' => fake()->paragraphs(2, true),
        ];
    }
}
