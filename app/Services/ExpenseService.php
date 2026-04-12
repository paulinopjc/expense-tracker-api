<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function create(User $user, array $data): Expense
    {
        if ($user->role !== UserRole::Admin && ! $user->team?->is_active) {
            throw ValidationException::withMessages([
                'team' => ['Your team has been disabled. Contact an administrator.'],
            ]);
        }

        return Expense::create([
            'user_id' => $user->id,
            'team_id' => $user->team_id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'date' => $data['date'],
            'status' => ExpenseStatus::Draft,
        ]);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense->fresh(['user', 'team', 'category']);
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }

    public function find(User $user, int $id): Expense
    {
        return Expense::with(['user', 'team', 'category', 'receipts'])
            ->findOrFail($id);
    }

    public function list(User $user, array $filters = [])
    {
        $query = Expense::with(['user', 'team', 'category']);

        // Scope by role
        if ($user->role === UserRole::Member) {
            $query->where('user_id', $user->id);
        } elseif ($user->role === UserRole::Manager) {
            $query->where('team_id', $user->team_id);
        }
        // Admin sees all — no filter

        // Apply filters
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        if (! empty($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (! empty($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        // Sorting
        $sortField = 'created_at';
        $sortDirection = 'desc';

        if (! empty($filters['sort'])) {
            $sort = $filters['sort'];
            if (str_starts_with($sort, '-')) {
                $sortDirection = 'desc';
                $sort = ltrim($sort, '-');
            } else {
                $sortDirection = 'asc';
            }

            $allowed = ['created_at', 'amount', 'date', 'title'];
            if (in_array($sort, $allowed)) {
                $sortField = $sort;
            }
        }

        $query->orderBy($sortField, $sortDirection);

        $perPage = min($filters['per_page'] ?? 15, 50);

        return $query->cursorPaginate($perPage);
    }

    public function submit(Expense $expense): Expense
    {
        if( !in_array($expense->status, [ExpenseStatus::Draft, ExpenseStatus::Rejected]) )
        {
            abort(422, 'Expense can only be submitted from draft or rejected status.');
        }

        $expense->update([
            'status' => ExpenseStatus::Pending,
            'rejection_reason' => null,
            'reviewer_id' => null,
            'reviewed_at' => null,
        ]);

        return $expense->fresh(['user', 'team', 'category']);
    }

    public function approve(User $reviewer, Expense $expense): Expense
    {
        if( $expense->status !== ExpenseStatus::Pending )
        {
            abort(422, 'Only pending expenses can be approved.');
        }

        $expense->update([
            'status' => ExpenseStatus::Approved,
            'reviewer_id' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $expense->fresh(['user', 'team', 'category']);
    }

    public function reject(User $reviewer, Expense $expense, string $reason): Expense
    {
        if( $expense->status !== ExpenseStatus::Pending )
        {
            abort(422, 'Only pending expenses can be rejected.');
        }

        $expense->update([
            'status' => ExpenseStatus::Rejected,
            'reviewer_id' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $expense->fresh(['user', 'team', 'category', 'reviewer']);
    }

}