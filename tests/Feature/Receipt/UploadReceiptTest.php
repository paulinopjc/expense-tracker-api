<?php

namespace Tests\Feature\Receipt;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadReceiptTest extends TestCase
{
    use RefreshDatabase;
    public function test_owner_can_upload_receipt(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $owner = User::factory()->create(["team_id" => $team->id]);

        $expense = Expense::factory()->forUser($owner)->create([
            'status' => ExpenseStatus::Draft,
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg', 800, 600)->size(500);

        $response = $this->actingAs($owner)->postJson("/api/expenses/{$expense->id}/receipts", [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'original_name', 'mime_type', 'size']]);

        $this->assertDatabaseHas('receipts', [
            'expense_id' => $expense->id,
            'original_name' => 'receipt.jpg',
        ]);

        $receipt = $expense->receipts()->first();
        Storage::disk('local')->assertExists($receipt->file_path);
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create([
            'status' => ExpenseStatus::Draft,
        ]);

        $file = UploadedFile::fake()->create('malware.exe', 500, 'application/x-msdownload');

        $response = $this->actingAs($user)
            ->postJson("/api/expenses/{$expense->id}/receipts", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_oversized_file(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create([
            'status' => ExpenseStatus::Draft,
        ]);

        // 11MB file — over the 10MB limit
        $file = UploadedFile::fake()->image('huge.jpg')->size(11000);

        $response = $this->actingAs($user)
            ->postJson("/api/expenses/{$expense->id}/receipts", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_can_list_receipts_for_expense(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create();

        // Upload two receipts using the factory
        \App\Models\Receipt::factory()->count(2)->create([
            'expense_id' => $expense->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/expenses/{$expense->id}/receipts");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_download_receipt(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $expense = Expense::factory()->forUser($user)->create();

        // Create a real file in fake storage
        $filePath = "receipts/{$expense->id}/test-receipt.jpg";
        Storage::disk('local')->put($filePath, 'fake file content');

        $receipt = \App\Models\Receipt::factory()->create([
            'expense_id' => $expense->id,
            'file_path' => $filePath,
            'original_name' => 'my-receipt.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/receipts/{$receipt->id}/download");

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition');
    }
}
