<?php

namespace Database\Factories;

use App\Enums\StatusKenaikan;
use App\Models\KenaikanTingkat;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KenaikanTingkat>
 */
class KenaikanTingkatFactory extends Factory
{
    protected $model = KenaikanTingkat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tingkat_asal_id' => TingkatSeleksi::factory(),
            'tingkat_tujuan_id' => null,
            'status' => StatusKenaikan::Lulus,
            'keterangan' => null,
        ];
    }

    public function lulus(?TingkatSeleksi $tujuan = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusKenaikan::Lulus,
            'tingkat_tujuan_id' => $tujuan?->id,
            'keterangan' => null,
        ]);
    }

    public function tidakLulus(?string $keterangan = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusKenaikan::TidakLulus,
            'tingkat_tujuan_id' => null,
            'keterangan' => $keterangan,
        ]);
    }
}
