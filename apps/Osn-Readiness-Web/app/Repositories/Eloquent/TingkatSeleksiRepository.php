<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Models\TingkatSeleksi;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class TingkatSeleksiRepository extends BaseRepository implements TingkatSeleksiRepositoryInterface
{
    public function __construct(TingkatSeleksi $model)
    {
        parent::__construct($model);
    }

    public function semuaTerurut(): Collection
    {
        return $this->model->newQuery()->orderBy('urutan')->get();
    }
}
