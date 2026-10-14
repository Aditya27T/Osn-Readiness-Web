<?php

namespace Database\Factories;

use App\Models\HasilSimulasi;
use App\Models\Pretest;
use App\Models\Simulasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HasilSimulasi>
 */
class HasilSimulasiFactory extends Factory
{
    protected $model = HasilSimulasi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = now();

        return [
            'user_id' => User::factory(),
            'simulasi_id' => Simulasi::factory(),
            'pretest_id' => Pretest::factory(),
            'mulai_pada' => $mulai,
            'batas_pada' => $mulai->copy()->addHours(2),
            'disubmit_pada' => null,
            'selesai_pada' => null,
            'nilai' => null,
            'jumlah_benar' => null,
            'jumlah_salah' => null,
            'lulus' => null,
        ];
    }

    public function berjalan(): static
    {
        return $this->state(fn (array $attributes): array => [
            'disubmit_pada' => null,
            'selesai_pada' => null,
            'nilai' => null,
            'jumlah_benar' => null,
            'jumlah_salah' => null,
            'lulus' => null,
        ]);
    }

    /**
     * Sudah disubmit tetapi belum dinilai: scheduler atau job yang menutupnya.
     */
    public function terkunci(): static
    {
        return $this->state(fn (array $attributes): array => [
            'disubmit_pada' => now(),
            'selesai_pada' => null,
            'nilai' => null,
            'jumlah_benar' => null,
            'jumlah_salah' => null,
            'lulus' => null,
        ]);
    }

    public function selesai(float $nilai = 80, bool $lulus = true): static
    {
        return $this->state(fn (array $attributes): array => [
            'disubmit_pada' => now(),
            'selesai_pada' => now(),
            'nilai' => $nilai,
            'jumlah_benar' => 24,
            'jumlah_salah' => 6,
            'lulus' => $lulus,
        ]);
    }

    /**
     * Batas waktu sudah lewat, dipakai test command penutupan otomatis.
     */
    public function lewatBatasWaktu(): static
    {
        return $this->state(fn (array $attributes): array => [
            'batas_pada' => now()->subMinute(),
        ]);
    }
}
