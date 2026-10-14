<?php

namespace App\Contracts\Repositories;

use App\Models\PretestJawaban;
use Illuminate\Database\Eloquent\Collection;

interface PretestJawabanRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Baris jawaban satu pre-test, dengan soal ikut dimuat.
     *
     * @return Collection<int, PretestJawaban>
     */
    public function untukPretest(int $pretestId): Collection;

    /**
     * Semua soal_id yang sudah pernah dipakai siswa di satu tingkat, dari
     * seluruh pre-test-nya. Inilah daftar dikecualikan agar soal tidak pernah
     * berulang di putaran berikutnya.
     *
     * @return array<int, int>
     */
    public function soalIdsTerpakai(int $userId, int $tingkatId): array;

    /**
     * @param  array<int, array{soal_id: int, urutan: int, bobot: int}>  $butir
     */
    public function buatBanyak(int $pretestId, array $butir): void;

    /**
     * Satu baris jawaban milik pre-test itu, atau null bila soalnya bukan
     * bagian dari pre-test tersebut.
     */
    public function findJawaban(int $pretestId, int $soalId): ?PretestJawaban;
}
