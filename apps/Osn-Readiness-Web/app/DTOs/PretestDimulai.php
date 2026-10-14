<?php

namespace App\DTOs;

use App\Models\Pretest;
use App\Models\Soal;
use Illuminate\Database\Eloquent\Collection;

/**
 * Hasil POST /api/pretest.
 *
 * $baru membedakan dua keadaan yang sama bentuk balasannya: pre-test yang
 * baru dibuat dibalas 201, pre-test yang sudah berjalan dikembalikan ulang
 * dengan 200 supaya siswa tidak kehilangan jawaban yang sudah ia isi.
 */
final class PretestDimulai
{
    /**
     * @param  Collection<int, Soal>  $soal  soal urut sesuai urutan tampil
     */
    public function __construct(
        public readonly Pretest $pretest,
        public readonly Collection $soal,
        public readonly bool $baru,
    ) {}
}
