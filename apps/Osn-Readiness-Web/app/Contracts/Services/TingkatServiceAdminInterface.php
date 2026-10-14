<?php

namespace App\Contracts\Services;

use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Collection;

interface TingkatServiceAdminInterface
{
    /**
     * @return Collection<int, TingkatSeleksi>
     */
    public function getAll(): Collection;

    public function getById(int $id): TingkatSeleksi;

    public function update(int $id, array $data): TingkatSeleksi;

    public function delete(int $id): bool;
}
