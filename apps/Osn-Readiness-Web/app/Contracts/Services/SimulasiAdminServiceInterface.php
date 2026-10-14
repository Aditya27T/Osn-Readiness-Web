<?php

namespace App\Contracts\Services;

use App\Models\Simulasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SimulasiAdminServiceInterface
{
    public function daftar(int $perPage = 15): LengthAwarePaginator;

    /**
     * is_aktif hanya bisa dinyalakan bila bank soal cukup untuk satu
     * percobaan utuh (422 pada is_aktif bila kurang).
     *
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data): Simulasi;

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(Simulasi $simulasi, array $data): Simulasi;

    /**
     * Ditolak dengan 409 bila simulasi sudah punya hasil pengerjaan.
     */
    public function hapus(Simulasi $simulasi): void;
}
