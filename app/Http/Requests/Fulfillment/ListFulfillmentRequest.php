<?php

namespace App\Http\Requests\Fulfillment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string validation for the paginated planning list. Filter
 * values are validated loosely (the filter classes handle invalid
 * tokens silently); sort is whitelisted by FulfillmentQuery.
 */
class ListFulfillmentRequest extends FormRequest
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
            'filter' => ['sometimes', 'array'],
            'filter.delivery_flow' => ['nullable', 'string', 'max:64'],
            'filter.department' => ['nullable', 'string', 'max:64'],
            'filter.on_tour' => ['nullable', 'string', 'max:32'],
            'filter.search' => ['nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'max:64'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
