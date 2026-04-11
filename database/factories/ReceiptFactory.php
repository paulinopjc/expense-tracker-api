<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            'file_path' => 'receipts/' . fake()->uuid() . '.jpg',
            'original_name' => fake()->word() . '_receipt.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10000, 5000000),
        ];
    }
}