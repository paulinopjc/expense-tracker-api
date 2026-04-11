<?php

namespace App\Policies;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function view(User $user, Expense $expense): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Manager && $user->team_id === $expense->team_id) {
            return true;
        }

        return $expense->user_id === $user->id;
    }

    public function update(User $user, Expense $expense): bool
    {
        if ($expense->user_id !== $user->id) {
            return false;
        }

        return in_array($expense->status, [
            ExpenseStatus::Draft,
            ExpenseStatus::Rejected,
        ]);
    }

    public function delete(User $user, Expense $expense): bool
    {
        if ($expense->user_id !== $user->id) {
            return false;
        }

        return $expense->status === ExpenseStatus::Draft;
    }

    public function submit(User $user, Expense $expense): bool
    {
        return $expense->user_id === $user->id;
    }

    public function approve(User $user, Expense $expense): bool
    {
        if( $user->role === UserRole::Admin )
            return true;

        return $user->role === UserRole::Manager && $user->team_id === $expense->team_id;
    }

    public function reject(User $user, Expense $expense): bool
    {
        return $this->approve($user, $expense);
    }
}