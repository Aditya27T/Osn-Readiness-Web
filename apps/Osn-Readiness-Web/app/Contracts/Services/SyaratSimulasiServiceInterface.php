<?php

namespace App\Contracts\Services;

use App\DTOs\SyaratSimulasi;
use App\Models\TingkatSeleksi;
use App\Models\User;

interface SyaratSimulasiServiceInterface
{
    /**
     * Periksa materi wajib dan nilai latihan putaran aktif untuk satu tingkat.
     *
     * Dipanggil dua kali: sekali untuk endpoint status (tampilan), sekali lagi
     * di dalam mulai simulasi sebagai penjaga yang sebenarnya.
     */
    public function periksa(User $user, TingkatSeleksi $tingkat): SyaratSimulasi;
}
