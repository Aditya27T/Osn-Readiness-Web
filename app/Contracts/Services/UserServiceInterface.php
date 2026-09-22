<?php

namespace App\Contracts\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    public function list(int $perPage = 15): LengthAwarePaginator;

    public function registerUser(array $data): User;

    public function updateUser(User $user, array $data): User;

    public function deleteUser(User $user): bool;
}
