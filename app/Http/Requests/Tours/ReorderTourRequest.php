<?php

namespace App\Http\Requests\Tours;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Re-sequence a draft tour from an explicit ordered list of order ids.
 * The service validates that the tour is still in Draft and renumbers
 * primary_sequence accordingly.
 */
class ReorderTourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:orders,id'],
        ];
    }
}
