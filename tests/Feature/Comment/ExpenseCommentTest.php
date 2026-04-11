<?php

namespace Tests\Feature\Comment;

use App\Models\Comment;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCommentTest extends TestCase
{
    use RefreshDatabase;
    public function test_owner_can_comment_on_own_expense(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create([
            "team_id" => $team->id,
        ]);

        $expense = Expense::factory()->forUser($owner)->create();

        $response = $this->actingAs($owner)->postJson("/api/expenses/{$expense->id}/comments", [
            "body" => "Attached the missing receipt.",
        ]);

        $response->assertStatus(201)
            ->assertJsonPath("data.body", "Attached the missing receipt.")
            ->assertJsonPath("data.user.id", $owner->id);

        $this->assertDatabaseHas('comments', [
            'user_id' => $owner->id,
            'commentable_type' => Expense::class,
            'commentable_id' => $expense->id,
            'body' => 'Attached the missing receipt.',
        ]);
    }

    public function test_manager_can_comment_on_team_expense(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($member)->create();

        $response = $this->actingAs($manager)
            ->postJson("/api/expenses/{$expense->id}/comments", [
                'body' => 'Please add the itemized receipt.',
            ]);

        $response->assertStatus(201);
    }

    public function test_member_cannot_comment_on_other_users_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $other = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($other)->create();

        $response = $this->actingAs($user)
            ->postJson("/api/expenses/{$expense->id}/comments", [
                'body' => 'Should not work.',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_list_comments_on_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create();

        Comment::factory()->count(3)->create([
            'commentable_type' => Expense::class,
            'commentable_id' => $expense->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/expenses/{$expense->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_owner_can_delete_own_comment(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => Expense::class,
            'commentable_id' => $expense->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Comment deleted.']);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_cannot_delete_other_users_comment(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $other = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create();
        $comment = Comment::factory()->create([
            'user_id' => $other->id,
            'commentable_type' => Expense::class,
            'commentable_id' => $expense->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(403);
    }
}
