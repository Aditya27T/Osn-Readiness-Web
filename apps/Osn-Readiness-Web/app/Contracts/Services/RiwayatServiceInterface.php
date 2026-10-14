<?php

namespace App\Contracts\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RiwayatServiceInterface
{
    /**
     * Riwayat hasil milik siswa, terbaru lebih dulu.
     *
     * @param  string|null  $jenis  pretest, latihan, atau simulasi
     */
    public function untukSiswa(User $user, ?string $jenis, ?int $tingkatId, int $perHalaman = 15): LengthAwarePaginator;
}
