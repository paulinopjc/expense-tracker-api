<?php

namespace App\Policies;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Expense;
use Illuminate\Auth\Access\Response;

class ReceiptPolicy
{
    public function upload(User $user, Expense $expense): bool
    {
        if ($expense->user_id !== $user->id) {
            return false;
        }

        return in_array($expense->status, [
            ExpenseStatus::Draft,
            ExpenseStatus::Rejected,
        ]);
    }

    public function download(User $user, Receipt $receipt): bool
    {
        $expense = $receipt->expense;

        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Manager && $user->team_id === $expense->team_id) {
            return true;
        }

        return $expense->user_id === $user->id;
    }

    public function delete(User $user, Receipt $receipt): bool
    {
        $expense = $receipt->expense;

        return $expense->user_id === $user->id
            && $expense->status === ExpenseStatus::Draft;
    }
}
