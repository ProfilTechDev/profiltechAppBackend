<?php

namespace App\Http\Requests\Tours;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Payload for placing an order on a tour. `after_primary` is required
 * only when inserting into an approved tour with an A/B/C suffix —
 * for draft tours and end-of-tour appends it can be omitted.
 */
class AddOrderToTourRequest extends FormRequest
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
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'after_primary' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'at_primary' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
