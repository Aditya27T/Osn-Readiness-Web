<?php

namespace App\Contracts\Repositories;

use App\Models\Pretest;

interface PretestRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Pre-test yang belum selesai di tingkat ini, kalau ada.
     */
    public function berjalan(int $userId, int $tingkatId): ?Pretest;

    /**
     * Pre-test terbaru yang sudah selesai di tingkat ini: putaran aktif.
     */
    public function putaranAktifTerbaru(int $userId, int $tingkatId): ?Pretest;

    /**
     * Cari pre-test milik siswa tertentu. Pre-test milik orang lain dianggap
     * tidak ada, sehingga pemanggil membalas 404 dan bukan 403.
     */
    public function findMilik(int $id, int $userId): ?Pretest;

    /**
     * Kunci baris untuk transaksi submit, supaya dua submit bersamaan tidak
     * bisa sama-sama mengisi disubmit_pada.
     */
    public function findUntukUpdate(int $id): ?Pretest;
}
