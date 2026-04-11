<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'date' => ['sometimes', 'date', 'before_or_equal:today'],
        ];
    }
}