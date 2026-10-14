<?php

namespace App\Contracts\Repositories;

use App\Models\Simulasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SimulasiRepositoryInterface
{
    /**
     * @return Collection<int, Simulasi>
     */
    public function untukTingkat(int $tingkatId): Collection;

    /**
     * Daftar simulasi untuk admin, terbaru dulu, termasuk yang nonaktif.
     */
    public function paginasiAdmin(int $perPage = 15): LengthAwarePaginator;

    /**
     * Apakah simulasi ini sudah punya hasil pengerjaan siswa.
     */
    public function punyaHasil(Simulasi $simulasi): bool;
}
