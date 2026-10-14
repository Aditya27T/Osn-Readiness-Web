<?php

namespace App\DTOs;

use App\Models\TingkatSeleksi;

/**
 * Satu tingkat beserta status putaran siswa di tingkat itu, dipakai GET /api/tingkat.
 */
final class TingkatSiswa
{
    public function __construct(
        public readonly TingkatSeleksi $tingkat,
        public readonly StatusPutaran $status,
    ) {}
}
