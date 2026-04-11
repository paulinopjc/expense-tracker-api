<?php

namespace Tests\Feature\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_only_sees_own_expenses(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $otherUser = User::factory()->create(['team_id' => $team->id]);

        Expense::factory()->forUser($user)->count(2)->create();
        Expense::factory()->forUser($otherUser)->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/expenses');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_manager_sees_team_expenses(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $outsider = User::factory()->create(['team_id' => $otherTeam->id]);

        Expense::factory()->forUser($member)->count(2)->create();
        Expense::factory()->forUser($outsider)->count(3)->create();

        $response = $this->actingAs($manager)
            ->getJson('/api/expenses');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_by_status(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        Expense::factory()->forUser($user)->create(['status' => ExpenseStatus::Draft]);
        Expense::factory()->forUser($user)->create(['status' => ExpenseStatus::Pending]);
        Expense::factory()->forUser($user)->create(['status' => ExpenseStatus::Approved]);

        $response = $this->actingAs($user)
            ->getJson('/api/expenses?status=draft');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_date_range(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $category = Category::factory()->create();

        Expense::factory()->forUser($user)->create([
            'date' => '2026-03-01',
            'category_id' => $category->id,
        ]);
        Expense::factory()->forUser($user)->create([
            'date' => '2026-03-15',
            'category_id' => $category->id,
        ]);
        Expense::factory()->forUser($user)->create([
            'date' => '2026-04-01',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/expenses?date_from=2026-03-01&date_to=2026-03-31');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}