<?php

namespace App\Contracts\Services;

use App\Models\Quiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LatihanAdminServiceInterface
{
    public function daftar(int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data): Quiz;

    /**
     * @param  array<string, mixed>  $data
     */
    public function perbarui(Quiz $quiz, array $data): Quiz;

    /**
     * Ditolak dengan 409 bila latihan sudah punya pengerjaan siswa.
     */
    public function hapus(Quiz $quiz): void;
}
