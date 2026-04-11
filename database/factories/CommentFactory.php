<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'commentable_type' => Expense::class,
            'commentable_id' => Expense::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}