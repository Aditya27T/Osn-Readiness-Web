<?php

namespace App\Services\Admin;

use App\Contracts\Repositories\QuizRepositoryInterface;
use App\Contracts\Services\LatihanAdminServiceInterface;
use App\Exceptions\LatihanMasihDigunakanException;
use App\Models\Quiz;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LatihanAdminService implements LatihanAdminServiceInterface
{
    public function __construct(
        private readonly QuizRepositoryInterface $quizRepository,
    ) {}

    public function daftar(int $perPage = 15): LengthAwarePaginator
    {
        return $this->quizRepository->paginasiAdmin($perPage);
    }

    public function buat(array $data): Quiz
    {
        /** @var Quiz */
        return $this->quizRepository->create($data);
    }

    public function perbarui(Quiz $quiz, array $data): Quiz
    {
        /** @var Quiz */
        return $this->quizRepository->update($quiz, $data);
    }

    public function hapus(Quiz $quiz): void
    {
        if ($this->quizRepository->punyaPengerjaan($quiz)) {
            throw new LatihanMasihDigunakanException;
        }

        $this->quizRepository->delete($quiz);
    }
}
