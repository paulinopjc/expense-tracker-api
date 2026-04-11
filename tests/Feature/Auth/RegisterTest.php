<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_team(): void
    {
        $team = Team::factory()->create();

        $response = $this->postJson('/api/register', [
            'name' => 'Paulino Awino',
            'email' => 'paulino@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'team_id' => $team->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['user' => ['id', 'name', 'email', 'role'], 'token'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'paulino@example.com',
            'team_id' => $team->id,
            'role' => 'member',
        ]);
    }

    public function test_registration_requires_valid_team(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'team_id' => 999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['team_id']);
    }

    public function test_registration_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'team_id']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        $team = Team::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'team_id' => $team->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}