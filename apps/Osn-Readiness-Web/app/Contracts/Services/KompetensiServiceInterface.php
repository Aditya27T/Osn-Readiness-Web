<?php

namespace App\Contracts\Services;

use App\Models\Kompetensi;
use Illuminate\Support\Collection;

interface KompetensiServiceInterface
{
    /**
     * @return Collection<int, Kompetensi>
     */
    public function getAll(): Collection;

    public function getById(int $id): Kompetensi;

    public function create(array $data): Kompetensi;

    public function update(int $id, array $data): Kompetensi;

    public function delete(int $id): bool;
}
