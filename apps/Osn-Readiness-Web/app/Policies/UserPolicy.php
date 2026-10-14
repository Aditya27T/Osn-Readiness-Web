<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('siswa.viewAny');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('siswa.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('siswa.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('siswa.update');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('siswa.delete');
    }

    public function deactivate(User $user, User $model): bool
    {
        return $user->hasPermissionTo('siswa.deactivate');
    }
}
