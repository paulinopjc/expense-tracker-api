<?php

namespace Tests\Feature\Expense;

use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_own_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $expense->id);
    }

    public function test_member_cannot_view_other_users_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $otherUser = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($otherUser)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/expenses/{$expense->id}");

        $response->assertStatus(403);
    }

    public function test_manager_can_view_team_expense(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($member)->create();

        $response = $this->actingAs($manager)
            ->getJson("/api/expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $expense->id);
    }

    public function test_admin_can_view_any_expense(): void
    {
        $admin = User::factory()->admin()->create();
        $expense = Expense::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $expense->id);
    }
}