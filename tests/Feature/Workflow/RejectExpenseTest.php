<?php

namespace Tests\Feature\Workflow;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RejectExpenseTest extends TestCase
{
    use RefreshDatabase;
    public function test_manager_can_reject_with_reason(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(["team_id" => $team->id]);
        $member = User::factory()->create(["team_id" => $team->id]);

        $expense = Expense::factory()->forUser($member)->pending()->create();

        $response = $this->actingAs($manager)->postJson("/api/expenses/{$expense->id}/reject", [
            "rejection_reason" => "You're a Diab",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ExpenseStatus::Rejected->value)
            ->assertJsonPath('data.rejection_reason', "You're a Diab");

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => ExpenseStatus::Rejected->value,
            'reviewer_id' => $manager->id,
            'rejection_reason' => "You're a Diab",
        ]);
    }

    public function test_rejection_requires_reason(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(["team_id" => $team->id]);
        $member = User::factory()->create(["team_id" => $team->id]);

        $expense = Expense::factory()->forUser($member)->pending()->create();

        $response = $this->actingAs($manager)->postJson("/api/expenses/{$expense->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('rejection_reason');
    }

    public function test_manager_cannot_reject_other_team_expense(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $teamA->id]);
        $member = User::factory()->create(['team_id' => $teamB->id]);
        $expense = Expense::factory()->forUser($member)->pending()->create();

        $response = $this->actingAs($manager)
            ->postJson("/api/expenses/{$expense->id}/reject", [
                'rejection_reason' => 'Should not work.',
            ]);

        $response->assertStatus(403);
    }
}
