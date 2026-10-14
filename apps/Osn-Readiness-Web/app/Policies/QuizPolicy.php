<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
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
        return $user->hasPermissionTo('quiz.viewAny');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quiz.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('quiz.create');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quiz.update');
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('quiz.delete');
    }
}
