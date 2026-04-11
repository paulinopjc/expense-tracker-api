<?php

namespace Tests\Feature\Expense;

use App\Models\Category;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $category = Category::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/expenses', [
                'title' => 'Client lunch',
                'amount' => 45.50,
                'category_id' => $category->id,
                'date' => '2026-03-15',
                'description' => 'Lunch with client to discuss Q3 contract',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Client lunch')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('expenses', [
            'title' => 'Client lunch',
            'user_id' => $user->id,
            'team_id' => $team->id,
            'status' => 'draft',
        ]);
    }

    public function test_expense_requires_valid_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/expenses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'amount', 'category_id', 'date']);
    }
}