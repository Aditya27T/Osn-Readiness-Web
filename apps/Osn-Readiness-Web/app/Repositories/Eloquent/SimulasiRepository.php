<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\SimulasiRepositoryInterface;
use App\Models\Simulasi;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SimulasiRepository extends BaseRepository implements SimulasiRepositoryInterface
{
    public function __construct(Simulasi $model)
    {
        parent::__construct($model);
    }

    public function untukTingkat(int $tingkatId): Collection
    {
        return $this->model->newQuery()
            ->where('tingkat_id', $tingkatId)
            ->where('is_aktif', true)
            ->orderBy('id')
            ->get();
    }

    public function paginasiAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('tingkat')
            ->latest()
            ->paginate($perPage);
    }

    public function punyaHasil(Simulasi $simulasi): bool
    {
        return $simulasi->hasilSimulasi()->exists();
    }
}
