<?php

namespace App\Policies;

use App\Models\AturanPemetaan;
use App\Models\User;

class AturanPemetaanPolicy
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
        return $user->hasPermissionTo('aturan-pemetaan.viewAny');
    }

    public function view(User $user, AturanPemetaan $aturanPemetaan): bool
    {
        return $user->hasPermissionTo('aturan-pemetaan.view');
    }

    public function update(User $user, AturanPemetaan $aturanPemetaan): bool
    {
        return $user->hasPermissionTo('aturan-pemetaan.update');
    }
}
