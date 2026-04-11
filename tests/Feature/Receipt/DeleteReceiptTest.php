<?php

namespace Tests\Feature\Receipt;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteReceiptTest extends TestCase
{
    use RefreshDatabase;
    public function test_owner_can_delete_receipt_from_draft_expense(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create([
            'status' => ExpenseStatus::Draft,
        ]);

        $filePath = "receipts/{$expense->id}/receipt.jpg";
        Storage::disk('local')->put($filePath, 'fake content');

        $receipt = Receipt::factory()->create([
            'expense_id' => $expense->id,
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/receipts/{$receipt->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Receipt deleted.']);

        $this->assertDatabaseMissing('receipts', ['id' => $receipt->id]);
        Storage::disk('local')->assertMissing($filePath);
    }

    public function test_cannot_delete_receipt_from_approved_expense(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->approved()->create();
        $receipt = Receipt::factory()->create(['expense_id' => $expense->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/receipts/{$receipt->id}");

        $response->assertStatus(403);
    }

    public function test_non_owner_cannot_delete_receipt(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create(['team_id' => $team->id]);
        $other = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($owner)->create([
            'status' => ExpenseStatus::Draft,
        ]);
        $receipt = Receipt::factory()->create(['expense_id' => $expense->id]);

        $response = $this->actingAs($other)
            ->deleteJson("/api/receipts/{$receipt->id}");

        $response->assertStatus(403);
    }
}
