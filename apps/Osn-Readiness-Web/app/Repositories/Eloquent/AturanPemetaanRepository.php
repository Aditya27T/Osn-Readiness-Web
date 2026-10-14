<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\AturanPemetaanRepositoryInterface;
use App\Models\AturanPemetaan;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class AturanPemetaanRepository extends BaseRepository implements AturanPemetaanRepositoryInterface
{
    public function __construct(AturanPemetaan $model)
    {
        parent::__construct($model);
    }

    public function untukTingkat(int $tingkatId): Collection
    {
        return $this->model->newQuery()
            ->where('tingkat_id', $tingkatId)
            ->get();
    }

    public function simpanBanyak(int $tingkatId, array $parameterKetentuan): void
    {
        foreach ($parameterKetentuan as $parameter => $ketentuan) {
            $this->model->newQuery()->updateOrCreate(
                ['tingkat_id' => $tingkatId, 'parameter' => $parameter],
                ['ketentuan' => (string) $ketentuan],
            );
        }
    }
}
