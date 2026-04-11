<?php

namespace Tests\Feature\Report;

use App\Enums\ExpenseStatus;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseReportTest extends TestCase
{
    use RefreshDatabase;
    public function test_manager_can_view_team_summary(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);

        Expense::factory()->forUser($member)->approved()->create(['amount' => 100.00]);
        Expense::factory()->forUser($member)->approved()->create(['amount' => 250.00]);
        Expense::factory()->forUser($member)->pending()->create(['amount' => 50.00]);

        $response = $this->actingAs($manager)
            ->getJson('/api/reports/team-summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_amount', '350.00')
            ->assertJsonPath('data.total_count', 2)
            ->assertJsonPath('data.average_amount', '175.00');
    }

    public function test_summary_only_includes_approved_by_default(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);

        Expense::factory()->forUser($member)->approved()->create(['amount' => 100.00]);
        Expense::factory()->forUser($member)->pending()->create(['amount' => 999.00]);
        Expense::factory()->forUser($member)->rejected()->create(['amount' => 888.00]);

        $response = $this->actingAs($manager)
            ->getJson('/api/reports/team-summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_count', 1)
            ->assertJsonPath('data.total_amount', '100.00');
    }

    public function test_can_filter_summary_by_date_range(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $category = Category::factory()->create();

        Expense::factory()->forUser($member)->approved()->create([
            'amount' => 100.00,
            'date' => '2026-01-15',
            'category_id' => $category->id,
        ]);
        Expense::factory()->forUser($member)->approved()->create([
            'amount' => 200.00,
            'date' => '2026-03-15',
            'category_id' => $category->id,
        ]);
        Expense::factory()->forUser($member)->approved()->create([
            'amount' => 300.00,
            'date' => '2026-06-15',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($manager)
            ->getJson('/api/reports/team-summary?date_from=2026-03-01&date_to=2026-03-31');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_amount', '200.00')
            ->assertJsonPath('data.total_count', 1);
    }

    public function test_manager_can_view_breakdown_by_category(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $team->id]);
        $member = User::factory()->create(['team_id' => $team->id]);

        $meals = Category::factory()->create(['name' => 'Meals']);
        $travel = Category::factory()->create(['name' => 'Travel']);

        Expense::factory()->forUser($member)->approved()->create([
            'amount' => 50.00,
            'category_id' => $meals->id,
        ]);
        Expense::factory()->forUser($member)->approved()->create([
            'amount' => 75.00,
            'category_id' => $meals->id,
        ]);
        Expense::factory()->forUser($member)->approved()->create([
            'amount' => 300.00,
            'category_id' => $travel->id,
        ]);

        $response = $this->actingAs($manager)
            ->getJson('/api/reports/by-category');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_manager_can_view_breakdown_by_member(): void
    {
        $team = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $team->id]);
        $member1 = User::factory()->create(['team_id' => $team->id, 'name' => 'Alice']);
        $member2 = User::factory()->create(['team_id' => $team->id, 'name' => 'Bob']);

        Expense::factory()->forUser($member1)->approved()->count(2)->create(['amount' => 100.00]);
        Expense::factory()->forUser($member2)->approved()->count(3)->create(['amount' => 50.00]);

        $response = $this->actingAs($manager)
            ->getJson('/api/reports/by-member');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_member_cannot_access_reports(): void
    {
        $team = Team::factory()->create();
        $member = User::factory()->create(['team_id' => $team->id]);

        $response = $this->actingAs($member)
            ->getJson('/api/reports/team-summary');

        $response->assertStatus(403);
    }

    public function test_manager_only_sees_own_team_data(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $manager = User::factory()->manager()->create(['team_id' => $teamA->id]);
        $memberA = User::factory()->create(['team_id' => $teamA->id]);
        $memberB = User::factory()->create(['team_id' => $teamB->id]);

        Expense::factory()->forUser($memberA)->approved()->create(['amount' => 100.00]);
        Expense::factory()->forUser($memberB)->approved()->create(['amount' => 999.00]);

        $response = $this->actingAs($manager)
            ->getJson('/api/reports/team-summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_amount', '100.00');
    }
}
