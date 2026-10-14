<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Baca saja di atas view riwayat_hasil. View tidak punya timestamps dan
 * tidak boleh ditulis, jadi kontrak ini tidak punya create/update/delete.
 */
interface RiwayatHasilRepositoryInterface
{
    /**
     * Baris milik siswa, terbaru lebih dulu, dengan pagination.
     *
     * @param  string|null  $jenis  pretest, latihan, atau simulasi
     */
    public function untukSiswa(User $user, ?string $jenis, ?int $tingkatId, int $perHalaman = 15): LengthAwarePaginator;
}
