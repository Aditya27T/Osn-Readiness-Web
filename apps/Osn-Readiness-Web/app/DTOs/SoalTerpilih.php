<?php

namespace App\DTOs;

use App\Enums\Level;

/**
 * Hasil pemilihan soal: satu paket lengkap, tidak pernah kurang dari yang
 * diminta. Urutan sudah diacak dan setiap butir sudah memakai nomor urutan
 * serta bobot yang diambil dari AturanService.
 *
 * @phpstan-type Butir array{
 *     soal_id: int,
 *     materi_id: int,
 *     level: Level,
 *     bobot: int,
 *     urutan: int
 * }
 */
final class SoalTerpilih
{
    /**
     * @param  array<int, array{soal_id: int, materi_id: int, level: Level, bobot: int, urutan: int}>  $butir
     */
    public function __construct(public readonly array $butir) {}

    public function jumlah(): int
    {
        return count($this->butir);
    }

    /**
     * @return array<int, int>
     */
    public function soalIds(): array
    {
        return array_column($this->butir, 'soal_id');
    }

    /**
     * Jumlah butir per nilai level, untuk keperluan pemeriksaan.
     *
     * @return array<string, int>
     */
    public function jumlahPerLevel(): array
    {
        $jumlah = [];

        foreach ($this->butir as $butir) {
            $jumlah[$butir['level']->value] = ($jumlah[$butir['level']->value] ?? 0) + 1;
        }

        ksort($jumlah);

        return $jumlah;
    }
}
