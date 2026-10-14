<?php

namespace App\Contracts\Repositories;

use App\Models\HasilSimulasiJawaban;
use Illuminate\Database\Eloquent\Collection;

interface HasilSimulasiJawabanRepositoryInterface
{
    /**
     * Baris jawaban satu percobaan simulasi, soal ikut dimuat.
     *
     * @return Collection<int, HasilSimulasiJawaban>
     */
    public function untukHasil(int $hasilId): Collection;

    /**
     * Satu baris jawaban untuk satu soal di satu percobaan.
     */
    public function findJawaban(int $hasilId, int $soalId): ?HasilSimulasiJawaban;

    /**
     * Semua soal yang pernah dipakai di putaran ini, diteruskan ke picker
     * sebagai daftar dihindari supaya percobaan kedua tidak mengulang.
     *
     * @return list<int>
     */
    public function soalIdsTerpakaiPutaran(int $pretestId): array;

    /**
     * @param  list<array{soal_id: int, urutan: int, bobot: int}>  $butir
     */
    public function buatBanyak(int $hasilId, array $butir): void;
}
