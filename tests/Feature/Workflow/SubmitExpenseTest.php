<?php

namespace Tests\Feature\Workflow;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmitExpenseTest extends TestCase
{
    use RefreshDatabase;
    public function test_owner_can_submit_draft_expense(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create(['team_id' => $team->id]);

        $expense = Expense::factory()->forUser($owner)->create([
            'status' => ExpenseStatus::Draft,
        ]);

        $response = $this->actingAs($owner)->postJson("/api/expenses/{$expense->id}/submit");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ExpenseStatus::Pending->value);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => ExpenseStatus::Pending->value,
        ]);

    }

    public function test_owner_can_resubmit_rejected_expense(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create(['team_id' => $team->id]);
        
        $expense = Expense::factory()->forUser($owner)->rejected()->create();

        $response = $this->actingAs($owner)->postJson("/api/expenses/{$expense->id}/submit");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ExpenseStatus::Pending->value);
    }

    public function test_owner_cannot_submit_already_pending_expense(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create(['team_id' => $team->id]);

        $expense = Expense::factory()->forUser($owner)->pending()->create();

        $response = $this->actingAs($owner)->postJson("/api/expenses/{$expense->id}/submit");
        $response->assertStatus(422);
    }

    public function test_non_owner_cannot_submit_expense():void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create(["team_id" => $team->id]);
        $nonowner = User::factory()->create(["team_id" => $team->id]);

        $expense = Expense::factory()->forUser($owner)->create([
            'status' => ExpenseStatus::Draft,
        ]);

        $response = $this->actingAs($nonowner)->post("/api/expenses/{$expense->id}/submit");
        $response->assertStatus(403);
    }

}
