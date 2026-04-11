<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Models\Category;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        return [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 5, 5000),
            'date' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => ExpenseStatus::Draft,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'team_id' => $user->team_id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Pending,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Approved,
            'reviewer_id' => User::factory()->manager(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExpenseStatus::Rejected,
            'reviewer_id' => User::factory()->manager(),
            'reviewed_at' => now(),
            'rejection_reason' => 'Missing receipt.',
        ]);
    }
}