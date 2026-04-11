<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\User;

class ReportService
{
    public function teamSummary(User $user, array $filters = []): array
    {
        $query = $this->baseQuery($user, $filters);

        $result = $query->selectRaw('
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(*) as total_count,
            COALESCE(AVG(amount), 0) as average_amount
        ')->first();

        return [
            'total_amount' => number_format($result->total_amount, 2, '.', ''),
            'total_count' => (int) $result->total_count,
            'average_amount' => number_format($result->average_amount, 2, '.', ''),
        ];
    }

    public function byCategory(User $user, array $filters = []): array
    {
        $query = $this->baseQuery($user, $filters);

        return $query
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->selectRaw('
                categories.id as category_id,
                categories.name as category_name,
                SUM(expenses.amount) as total_amount,
                COUNT(*) as total_count
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'category_id' => $row->category_id,
                'category_name' => $row->category_name,
                'total_amount' => number_format($row->total_amount, 2, '.', ''),
                'total_count' => (int) $row->total_count,
            ])
            ->toArray();
    }

    public function byMember(User $user, array $filters = []): array
    {
        $query = $this->baseQuery($user, $filters);

        return $query
            ->join('users', 'expenses.user_id', '=', 'users.id')
            ->selectRaw('
                users.id as user_id,
                users.name as user_name,
                SUM(expenses.amount) as total_amount,
                COUNT(*) as total_count
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'user_name' => $row->user_name,
                'total_amount' => number_format($row->total_amount, 2, '.', ''),
                'total_count' => (int) $row->total_count,
            ])
            ->toArray();
    }

    private function baseQuery(User $user, array $filters = [])
    {
        $query = Expense::query()->approved();

        // Team scoping
        if ($user->role === UserRole::Manager) {
            $query->forTeam($user->team_id);
        }
        // Admin sees all teams — no filter

        // Date range
        $query->dateRange(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null
        );

        return $query;
    }
}