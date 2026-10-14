<?php

namespace App\Policies;

use App\Models\Materi;
use App\Models\User;

class MateriPolicy
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
        return $user->hasPermissionTo('materi.viewAny');
    }

    public function view(User $user, Materi $model): bool
    {
        return $user->hasPermissionTo('materi.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('materi.create');
    }

    public function update(User $user, Materi $model): bool
    {
        return $user->hasPermissionTo('materi.update');
    }

    public function delete(User $user, Materi $model): bool
    {
        return $user->hasPermissionTo('materi.delete');
    }
}
