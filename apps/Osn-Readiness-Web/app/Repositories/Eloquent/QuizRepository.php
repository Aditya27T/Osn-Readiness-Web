<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\QuizRepositoryInterface;
use App\Models\Quiz;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuizRepository extends BaseRepository implements QuizRepositoryInterface
{
    public function __construct(Quiz $model)
    {
        parent::__construct($model);
    }

    public function paginasiAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('materi')
            ->latest()
            ->paginate($perPage);
    }

    public function punyaPengerjaan(Quiz $quiz): bool
    {
        return $quiz->pengerjaan()->exists();
    }
}
