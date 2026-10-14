<?php

namespace App\Contracts\Repositories;

interface KenaikanTingkatRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Apakah siswa sudah lulus tingkat ini (satu baris status lulus).
     */
    public function adaLulus(int $userId, int $tingkatId): bool;

    /**
     * Catat kelulusan. Bila baris lulus untuk siswa dan tingkat itu sudah
     * ada (index kenaikan_lulus_unique), tidak menulis apa pun dan tidak
     * melempar error, sehingga transaksi pemanggil tetap utuh.
     */
    public function catatLulus(int $userId, int $tingkatAsalId, ?int $tingkatTujuanId): void;

    /**
     * Catat putaran yang habis tanpa lulus.
     */
    public function catatTidakLulus(int $userId, int $tingkatAsalId, string $keterangan): void;
}
