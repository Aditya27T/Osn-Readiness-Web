<?php

namespace App\Contracts\Services;

use App\DTOs\TingkatSiswa;
use App\Models\User;
use Illuminate\Support\Collection;

interface TingkatServiceInterface
{
    /**
     * Semua tingkat berurutan beserta tingkat_terbuka dan tahap untuk siswa ini.
     *
     * @return Collection<int, TingkatSiswa>
     */
    public function daftar(User $user): Collection;
}
