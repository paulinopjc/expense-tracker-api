<?php

namespace Tests\Feature\Expense;

use App\Enums\ExpenseStatus;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_draft_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create([
            'status' => ExpenseStatus::Draft,
        ]);
        $newCategory = Category::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("/api/expenses/{$expense->id}", [
                'title' => 'Updated title',
                'amount' => 99.99,
                'category_id' => $newCategory->id,
                'date' => '2026-03-20',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated title');
    }

    public function test_cannot_update_approved_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->approved()->create();

        $response = $this->actingAs($user)
            ->putJson("/api/expenses/{$expense->id}", [
                'title' => 'Should not work',
            ]);

        $response->assertStatus(403);
    }
}