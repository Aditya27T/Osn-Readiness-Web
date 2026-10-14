<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\RiwayatHasilRepositoryInterface;
use App\Models\RiwayatHasil;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RiwayatHasilRepository implements RiwayatHasilRepositoryInterface
{
    public function __construct(
        private readonly RiwayatHasil $model,
    ) {}

    public function untukSiswa(User $user, ?string $jenis, ?int $tingkatId, int $perHalaman = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->when($jenis !== null, fn ($query) => $query->where('jenis_hasil', $jenis))
            ->when($tingkatId !== null, fn ($query) => $query->where('tingkat_id', $tingkatId))
            ->orderByDesc('tanggal')
            ->orderByDesc('referensi_id')
            ->paginate($perHalaman);
    }
}
