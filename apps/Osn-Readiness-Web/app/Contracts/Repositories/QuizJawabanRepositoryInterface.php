<?php

namespace App\Contracts\Repositories;

use App\Models\QuizJawaban;
use Illuminate\Database\Eloquent\Collection;

interface QuizJawabanRepositoryInterface
{
    /**
     * Baris jawaban satu pengerjaan quiz, soal ikut dimuat.
     *
     * @return Collection<int, QuizJawaban>
     */
    public function untukPengerjaan(int $pengerjaanId): Collection;

    /**
     * Satu baris jawaban untuk satu soal di satu pengerjaan.
     */
    public function findJawaban(int $pengerjaanId, int $soalId): ?QuizJawaban;

    /**
     * Semua soal yang pernah dijawab siswa di tingkat itu, dipakai picker
     * untuk menghindari pengulangan.
     *
     * @return list<int>
     */
    public function soalIdsTerpakai(int $userId, int $tingkatId): array;

    /**
     * @param  list<array{soal_id: int, urutan: int, bobot: int}>  $butir
     */
    public function buatBanyak(int $pengerjaanId, array $butir): void;
}
