<?php

namespace App\Contracts\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Kelola akun siswa oleh admin. Semua method hanya menyentuh akun ber-role
 * siswa; akun lain (termasuk admin) dianggap tidak ada (404).
 */
interface SiswaServiceInterface
{
    public function getAll(int $perPage = 15): LengthAwarePaginator;

    public function getById(int $id): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): User;

    /**
     * Nonaktifkan dan cabut semua token siswa itu.
     */
    public function deactivate(int $id): User;

    /**
     * Soft delete dan cabut semua token siswa itu.
     */
    public function delete(int $id): void;
}
