<?php

namespace App\Http\Requests\Tours;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create payload for a new tour. Status is fixed to Draft by the
 * service — clients don't get to set it directly.
 */
class StoreTourRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'tour_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
