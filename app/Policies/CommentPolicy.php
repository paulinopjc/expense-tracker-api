<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Expense;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user, Expense $expense): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Manager && $user->team_id === $expense->team_id) {
            return true;
        }

        return $expense->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }
}
