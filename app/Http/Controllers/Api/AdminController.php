<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\UserResource;
use App\Models\Category;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    public function toggleUser(User $user): JsonResponse
    {
        Gate::authorize('admin');

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'data' => new UserResource($user),
            'message' => $user->is_active ? 'User activated.' : 'User deactivated.',
        ]);
    }

    public function toggleTeam(Team $team): JsonResponse
    {
        Gate::authorize('admin');

        $team->update(['is_active' => !$team->is_active]);

        return response()->json([
            'data' => $team,
            'message' => $team->is_active ? 'Team activated.' : 'Team deactivated.',
        ]);
    }

    public function toggleCategory(Category $category): JsonResponse
    {
        Gate::authorize('admin');

        $category->update(['is_active' => !$category->is_active]);

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => $category->is_active ? 'Category activated.' : 'Category deactivated.',
        ]);
    }

    public function createTeam(Request $request): JsonResponse
    {
        Gate::authorize('admin');

        $validated = $request->validate(['name' => 'required|string|max:255|unique:teams']);
        $team = Team::create(['name' => $validated['name'], 'is_active' => true]);

        return response()->json(['data' => $team, 'message' => 'Team created.'], 201);
    }

    public function listTeams(): JsonResponse
    {
        Gate::authorize('admin');

        return response()->json(['data' => Team::orderBy('name')->get()]);
    }

    public function assignTeam(Request $request, User $user): JsonResponse
    {
        Gate::authorize('admin');

        $validated = $request->validate(['team_id' => 'required|exists:teams,id']);
        $user->update(['team_id' => $validated['team_id']]);

        return response()->json(['data' => new UserResource($user), 'message' => 'Team assigned.']);
    }
}