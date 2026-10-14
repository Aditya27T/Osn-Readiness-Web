<?php

namespace App\Contracts\Repositories;

use App\Models\QuizPengerjaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface QuizPengerjaanRepositoryInterface
{
    /**
     * Pengerjaan quiz yang belum disubmit untuk satu quiz milik satu siswa.
     */
    public function berjalan(User $user, int $quizId): ?QuizPengerjaan;

    /**
     * Satu pengerjaan milik siswa ini; milik orang lain dianggap tidak ada.
     */
    public function findMilik(int $id, int $userId): ?QuizPengerjaan;

    /**
     * Satu pengerjaan tanpa kunci baris.
     */
    public function find(int $id): ?QuizPengerjaan;

    /**
     * Satu pengerjaan dengan kunci baris, untuk transaksi submit.
     */
    public function findUntukUpdate(int $id): ?QuizPengerjaan;

    /**
     * Semua pengerjaan selesai untuk satu quiz milik satu siswa, dipakai
     * mencari nilai terbaik.
     *
     * @return Collection<int, QuizPengerjaan>
     */
    public function selesai(User $user, int $quizId): Collection;

    /**
     * Nilai tertinggi pengerjaan selesai milik satu siswa untuk tiap quiz,
     * dalam satu query. Quiz yang belum pernah selesai tidak ikut.
     *
     * @param  list<int>  $quizIds
     * @return array<int, float> diindeks quiz_id
     */
    public function nilaiTerbaikPerQuiz(User $user, array $quizIds): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): QuizPengerjaan;

    /**
     * Hapus semua pengerjaan latihan milik satu siswa untuk materi-materi
     * yang diberikan (jawabannya ikut terhapus lewat cascade), dipakai saat
     * putaran habis.
     *
     * @param  list<int>  $materiIds
     */
    public function hapusUntukMateri(User $user, array $materiIds): void;
}
