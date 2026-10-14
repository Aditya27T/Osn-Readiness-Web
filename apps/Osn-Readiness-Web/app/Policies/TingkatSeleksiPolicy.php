<?php

namespace App\Policies;

use App\Models\TingkatSeleksi;
use App\Models\User;

class TingkatSeleksiPolicy
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
        return $user->hasPermissionTo('tingkat.viewAny');
    }

    public function view(User $user, TingkatSeleksi $model): bool
    {
        return $user->hasPermissionTo('tingkat.view');
    }

    public function update(User $user, TingkatSeleksi $model): bool
    {
        return $user->hasPermissionTo('tingkat.update');
    }
}
