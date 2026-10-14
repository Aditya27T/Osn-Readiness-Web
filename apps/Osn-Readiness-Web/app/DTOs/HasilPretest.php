<?php

namespace App\DTOs;

use App\Models\PemetaanMateri;
use App\Models\Pretest;
use App\Models\RekomendasiMateri;
use Illuminate\Support\Collection;

/**
 * Hasil penilaian pre-test, dipakai baik oleh submit langsung maupun oleh
 * NilaiUlangJob yang penyelesaiannya terlambat.
 */
final class HasilPretest
{
    /**
     * @param  Collection<int, PemetaanMateri>  $pemetaan
     * @param  Collection<int, RekomendasiMateri>  $materiWajib
     */
    public function __construct(
        public readonly Pretest $pretest,
        public readonly Collection $pemetaan,
        public readonly Collection $materiWajib,
    ) {}
}
