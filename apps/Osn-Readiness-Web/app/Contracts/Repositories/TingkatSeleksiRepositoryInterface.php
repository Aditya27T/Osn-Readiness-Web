<?php

namespace App\Contracts\Repositories;

use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Collection;

interface TingkatSeleksiRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, TingkatSeleksi>
     */
    public function semuaTerurut(): Collection;
}
