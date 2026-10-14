<?php

namespace App\Contracts\Services;

interface DashboardAdminServiceInterface
{
    /**
     * Ringkasan angka untuk layar dashboard Super Admin: siswa aktif,
     * pengerjaan per jenis, siswa per tingkat aktif, dan rata-rata nilai
     * per jenis.
     *
     * @return array<string, mixed>
     */
    public function ringkasan(): array;
}
