<?php

namespace App\Contracts\Services;

use App\Models\User;

/**
 * Menyusun layar dashboard siswa (BE-15). Hanya membaca: seluruh angka
 * berasal dari PutaranService dan SyaratSimulasiService, supaya apa yang
 * ditampilkan selalu sama dengan yang dipakai penjaga simulasi.
 */
interface DashboardServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function untuk(User $user): array;
}
