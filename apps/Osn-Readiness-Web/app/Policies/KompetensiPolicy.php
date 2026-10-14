<?php

namespace App\Policies;

use App\Models\Kompetensi;
use App\Models\User;

class KompetensiPolicy
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
        return $user->hasPermissionTo('kompetensi.viewAny');
    }

    public function view(User $user, Kompetensi $model): bool
    {
        return $user->hasPermissionTo('kompetensi.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('kompetensi.create');
    }

    public function update(User $user, Kompetensi $model): bool
    {
        return $user->hasPermissionTo('kompetensi.update');
    }

    public function delete(User $user, Kompetensi $model): bool
    {
        return $user->hasPermissionTo('kompetensi.delete');
    }
}
