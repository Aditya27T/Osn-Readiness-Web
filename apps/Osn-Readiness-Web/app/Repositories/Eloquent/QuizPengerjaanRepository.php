<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\QuizPengerjaanRepositoryInterface;
use App\Models\QuizPengerjaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class QuizPengerjaanRepository implements QuizPengerjaanRepositoryInterface
{
    public function __construct(
        private readonly QuizPengerjaan $model,
    ) {}

    public function berjalan(User $user, int $quizId): ?QuizPengerjaan
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->whereNull('disubmit_pada')
            ->first();
    }

    public function findMilik(int $id, int $userId): ?QuizPengerjaan
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function find(int $id): ?QuizPengerjaan
    {
        return $this->model->newQuery()->find($id);
    }

    public function findUntukUpdate(int $id): ?QuizPengerjaan
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    public function selesai(User $user, int $quizId): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->whereNotNull('selesai_pada')
            ->orderByDesc('nilai')
            ->get();
    }

    public function nilaiTerbaikPerQuiz(User $user, array $quizIds): array
    {
        if ($quizIds === []) {
            return [];
        }

        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->whereNotNull('selesai_pada')
            ->whereNotNull('nilai')
            ->groupBy('quiz_id')
            ->selectRaw('quiz_id, max(nilai) as terbaik')
            ->pluck('terbaik', 'quiz_id')
            ->map(fn ($nilai): float => (float) $nilai)
            ->all();
    }

    public function create(array $data): QuizPengerjaan
    {
        /** @var QuizPengerjaan */
        return $this->model->newQuery()->create($data);
    }

    public function hapusUntukMateri(User $user, array $materiIds): void
    {
        $this->model->newQuery()
            ->where('user_id', $user->id)
            ->whereHas('quiz', function ($query) use ($materiIds): void {
                $query->whereIn('materi_id', $materiIds);
            })
            ->delete();
    }
}
