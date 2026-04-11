<?php

namespace Tests\Feature\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_soft_delete_draft_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create([
            'status' => ExpenseStatus::Draft,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Expense deleted.']);

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_cannot_delete_pending_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->pending()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/expenses/{$expense->id}");

        $response->assertStatus(403);
    }
}