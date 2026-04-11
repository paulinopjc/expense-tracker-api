<?php

namespace Tests\Feature\Workflow;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproveExpenseTest extends TestCase
{
    use RefreshDatabase;
    public function test_manager_can_approve_pending_team_expense(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(["team_id" => $team->id]);
        $member = User::factory()->create(["team_id" => $team->id ]);

        $expense = Expense::factory()->forUser($member)->pending()->create();

        $response = $this->actingAs($manager)->post("/api/expenses/{$expense->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ExpenseStatus::Approved->value);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => ExpenseStatus::Approved->value,
            'reviewer_id' => $manager->id,
        ]);
    }

    public function test_member_cannot_approve_expense(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(["team_id" => $team->id]);

        $expense = Expense::factory()->forUser($member)->pending()->create();

        $response = $this->actingAs($member)->post("/api/expenses/{$expense->id}/approve");

        $response->assertStatus(403);
    }

    public function test_manager_cannot_approve_draft_expense(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(["team_id" => $team->id]);
        $member = User::factory()->create(["team_id" => $team->id ]);

        $expense = Expense::factory()->forUser($member)->create([
            'status' => ExpenseStatus::Draft,
        ]);

        $response = $this->actingAs($manager)->post("/api/expenses/{$expense->id}/approve");

        $response->assertStatus(422);
    }
}
