<?php

namespace App\Contracts\Repositories;

use App\Models\Quiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuizRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Daftar latihan untuk admin, terbaru dulu, beserta materinya.
     */
    public function paginasiAdmin(int $perPage = 15): LengthAwarePaginator;

    /**
     * Apakah latihan ini sudah pernah dikerjakan siswa.
     */
    public function punyaPengerjaan(Quiz $quiz): bool;
}
