<?php

namespace App\Policies;

use App\Models\KonteksSoal;
use App\Models\User;

class KonteksSoalPolicy
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
        return $user->hasPermissionTo('konteks-soal.viewAny');
    }

    public function view(User $user, KonteksSoal $model): bool
    {
        return $user->hasPermissionTo('konteks-soal.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('konteks-soal.create');
    }

    public function update(User $user, KonteksSoal $model): bool
    {
        return $user->hasPermissionTo('konteks-soal.update');
    }

    public function delete(User $user, KonteksSoal $model): bool
    {
        return $user->hasPermissionTo('konteks-soal.delete');
    }
}
