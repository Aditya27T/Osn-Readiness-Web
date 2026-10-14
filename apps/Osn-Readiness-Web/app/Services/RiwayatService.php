<?php

namespace App\Services;

use App\Contracts\Repositories\RiwayatHasilRepositoryInterface;
use App\Contracts\Services\RiwayatServiceInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RiwayatService implements RiwayatServiceInterface
{
    public function __construct(
        private readonly RiwayatHasilRepositoryInterface $riwayatRepository,
    ) {}

    public function untukSiswa(User $user, ?string $jenis, ?int $tingkatId, int $perHalaman = 15): LengthAwarePaginator
    {
        return $this->riwayatRepository->untukSiswa($user, $jenis, $tingkatId, $perHalaman);
    }
}
