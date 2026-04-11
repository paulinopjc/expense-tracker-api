<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/categories', [
                'name' => 'Travel',
                'description' => 'Business travel expenses',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Travel');

        $this->assertDatabaseHas('categories', ['name' => 'Travel']);
    }

    public function test_member_cannot_create_category(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)
            ->postJson('/api/categories', [
                'name' => 'Travel',
            ]);

        $response->assertStatus(403);
    }

    public function test_anyone_can_list_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}