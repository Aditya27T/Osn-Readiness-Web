<?php

namespace App\Contracts\Services;

use App\DTOs\StatusPutaran;
use App\Models\TingkatSeleksi;
use App\Models\User;

interface PutaranServiceInterface
{
    /**
     * Turunkan keadaan siswa di satu tingkat dari data. Tidak ada kolom status.
     */
    public function status(User $user, TingkatSeleksi $tingkat): StatusPutaran;
}
