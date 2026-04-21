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

        return $query
            ->join('teams', 'expenses.team_id', '=', 'teams.id')
            ->selectRaw('
                teams.id as team_id,
                teams.name as name,
                COUNT(*) as count,
                COALESCE(SUM(expenses.amount), 0) as total
            ')
            ->groupBy('teams.id', 'teams.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'team_id' => (int) $row->team_id,
                'name' => $row->name,
                'count' => (int) $row->count,
                'total' => (float) $row->total,
            ])
            ->toArray();
    }

    public function byCategory(User $user, array $filters = []): array
    {
        $query = $this->baseQuery($user, $filters);

        return $query
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->selectRaw('
                categories.id as category_id,
                categories.name as name,
                COUNT(*) as count,
                COALESCE(SUM(expenses.amount), 0) as total
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category_id' => (int) $row->category_id,
                'name' => $row->name,
                'count' => (int) $row->count,
                'total' => (float) $row->total,
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
                users.name as name,
                COUNT(*) as count,
                COALESCE(SUM(expenses.amount), 0) as total
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->user_id,
                'name' => $row->name,
                'count' => (int) $row->count,
                'total' => (float) $row->total,
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
        // Admin sees all teams

        // Date range
        $query->dateRange(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null
        );

        return $query;
    }
}
