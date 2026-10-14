<?php

namespace App\DTOs;

use App\Models\QuizPengerjaan;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Collection;

/**
 * Latihan yang sedang berjalan atau baru dibuat, lengkap dengan daftar soal
 * pengerjaannya, mirip PretestDimulai.
 */
final class LatihanDimulai
{
    /**
     * @param  Collection<int, Soal>  $soal
     */
    public function __construct(
        public readonly QuizPengerjaan $pengerjaan,
        public readonly Collection $soal,
        public readonly bool $baru,
    ) {}
}
