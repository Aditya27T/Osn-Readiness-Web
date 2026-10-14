<?php

namespace App\Contracts\Repositories;

use App\Models\PemetaanMateri;
use Illuminate\Database\Eloquent\Collection;

interface PemetaanMateriRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param  array<int, array{materi_id: int, jumlah_soal: int, jumlah_benar: int, poin_didapat: int, poin_maksimal: int, persentase: float, peringkat: int}>  $baris
     */
    public function buatBanyak(int $pretestId, int $userId, array $baris): void;

    /**
     * @return Collection<int, PemetaanMateri>
     */
    public function untukPretest(int $pretestId): Collection;
}
