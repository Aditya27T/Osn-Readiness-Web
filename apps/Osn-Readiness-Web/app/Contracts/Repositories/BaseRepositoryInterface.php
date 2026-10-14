<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /** @param array<int, string> $columns */
    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int|string $id): ?Model;

    public function findOrFail(int|string $id): Model;

    /** @param array<string, mixed> $data */
    public function create(array $data): Model;

    /** @param array<string, mixed> $data */
    public function update(Model $model, array $data): Model;

    public function delete(Model $model): bool;
}
