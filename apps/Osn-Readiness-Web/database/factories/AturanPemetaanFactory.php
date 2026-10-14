<?php

namespace Database\Factories;

use App\Models\AturanPemetaan;
use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AturanPemetaan>
 */
class AturanPemetaanFactory extends Factory
{
    protected $model = AturanPemetaan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tingkat_id' => TingkatSeleksi::factory(),
            'parameter' => fake()->unique()->slug(2),
            'ketentuan' => (string) fake()->numberBetween(1, 100),
        ];
    }
}
