<?php

namespace App\Http\Requests\Expense;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'category_id' => ['required', 'exists:categories,id', function ($attribute, $value, $fail) {
                $category = Category::find($value);
                if ($category && !$category->is_active) {
                    $fail('This category has been disabled.');
                }
            }],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}