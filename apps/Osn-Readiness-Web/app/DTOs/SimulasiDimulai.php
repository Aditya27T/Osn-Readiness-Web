<?php

namespace App\DTOs;

use App\Models\HasilSimulasi;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Collection;

/**
 * Simulasi yang sedang berjalan atau baru dibuat, lengkap dengan daftar soal
 * pengerjaannya, mirip PretestDimulai.
 */
final class SimulasiDimulai
{
    /**
     * @param  Collection<int, Soal>  $soal
     */
    public function __construct(
        public readonly HasilSimulasi $hasil,
        public readonly Collection $soal,
        public readonly bool $baru,
    ) {}
}
