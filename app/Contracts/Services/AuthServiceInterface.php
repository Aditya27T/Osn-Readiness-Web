<?php

namespace App\Contracts\Services;

use App\Models\User;

interface AuthServiceInterface
{
    public function login(string $email, string $password, ?string $device = null): array;

    public function logout(User $user): bool;
}
