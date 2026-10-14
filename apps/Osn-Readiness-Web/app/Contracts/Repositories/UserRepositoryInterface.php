<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * Akun ber-role siswa, aktif maupun nonaktif, terbaru dulu. Akun admin
     * tidak termasuk.
     */
    public function paginasiSiswa(int $perPage = 15): LengthAwarePaginator;

    /**
     * Satu akun ber-role siswa; akun lain dianggap tidak ada.
     */
    public function cariSiswa(int $id): ?User;
}
