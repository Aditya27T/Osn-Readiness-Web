<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\PretestRepositoryInterface;
use App\Models\Pretest;
use App\Repositories\BaseRepository;

class PretestRepository extends BaseRepository implements PretestRepositoryInterface
{
    public function __construct(Pretest $model)
    {
        parent::__construct($model);
    }

    public function berjalan(int $userId, int $tingkatId): ?Pretest
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('tingkat_id', $tingkatId)
            ->whereNull('selesai_pada')
            ->first();
    }

    public function putaranAktifTerbaru(int $userId, int $tingkatId): ?Pretest
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('tingkat_id', $tingkatId)
            ->whereNotNull('selesai_pada')
            ->latest('selesai_pada')
            ->first();
    }

    public function findMilik(int $id, int $userId): ?Pretest
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function findUntukUpdate(int $id): ?Pretest
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();
    }
}
