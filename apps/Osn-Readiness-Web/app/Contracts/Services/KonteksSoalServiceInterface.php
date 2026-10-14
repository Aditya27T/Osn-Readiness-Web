<?php

namespace App\Contracts\Services;

use App\Models\KonteksSoal;
use Illuminate\Support\Collection;

interface KonteksSoalServiceInterface
{
    /**
     * @return Collection<int, KonteksSoal>
     */
    public function getAll(): Collection;

    public function getById(int $id): KonteksSoal;

    public function create(array $data): KonteksSoal;

    public function update(int $id, array $data): KonteksSoal;

    public function delete(int $id): bool;
}
