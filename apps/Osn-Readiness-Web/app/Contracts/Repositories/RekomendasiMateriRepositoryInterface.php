<?php

namespace App\Contracts\Repositories;

use App\Models\RekomendasiMateri;
use Illuminate\Database\Eloquent\Collection;

interface RekomendasiMateriRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param  array<int, array{materi_id: int, prioritas: int}>  $baris
     */
    public function buatBanyak(int $pretestId, int $userId, array $baris): void;

    /**
     * @return Collection<int, RekomendasiMateri>
     */
    public function untukPretest(int $pretestId): Collection;
}
