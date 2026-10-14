<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\HasilSimulasiRepositoryInterface;
use App\Models\HasilSimulasi;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class HasilSimulasiRepository extends BaseRepository implements HasilSimulasiRepositoryInterface
{
    public function __construct(HasilSimulasi $model)
    {
        parent::__construct($model);
    }

    public function berjalan(int $userId, int $pretestId): ?HasilSimulasi
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('pretest_id', $pretestId)
            ->whereNull('selesai_pada')
            ->first();
    }

    public function jumlahPercobaan(int $pretestId): int
    {
        return $this->model->newQuery()
            ->where('pretest_id', $pretestId)
            ->count();
    }

    public function jumlahSelesai(int $pretestId): int
    {
        return $this->model->newQuery()
            ->where('pretest_id', $pretestId)
            ->whereNotNull('selesai_pada')
            ->count();
    }

    public function nilaiTerbaik(int $pretestId): ?float
    {
        $nilai = $this->model->newQuery()
            ->where('pretest_id', $pretestId)
            ->whereNotNull('nilai')
            ->max('nilai');

        return $nilai === null ? null : (float) $nilai;
    }

    public function terakhirSelesai(int $userId, int $pretestId): ?HasilSimulasi
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('pretest_id', $pretestId)
            ->whereNotNull('selesai_pada')
            ->orderByDesc('selesai_pada')
            ->first();
    }

    public function findMilik(int $id, int $userId): ?HasilSimulasi
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function findUntukUpdate(int $id): ?HasilSimulasi
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    public function kedaluwarsa(int $batasToleransiDetik): Collection
    {
        return $this->model->newQuery()
            ->whereNull('disubmit_pada')
            ->whereRaw(
                "\"batas_pada\" + make_interval(secs => {$batasToleransiDetik}) <= now()",
            )
            ->orderBy('batas_pada')
            ->get();
    }

    public function create(array $data): HasilSimulasi
    {
        /** @var HasilSimulasi */
        return $this->model->newQuery()->create($data);
    }
}
