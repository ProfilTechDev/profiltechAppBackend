<?php

namespace App\Http\Requests\Tours;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string validation for the paginated tour list. Sort is
 * validated loosely (length only) — the query class owns the
 * whitelist of allowed sort fields.
 */
class ListToursRequest extends FormRequest
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
            'filter.status' => ['nullable', 'string', 'max:64'],
            'filter.date_range' => ['nullable', 'string', 'max:32'],
            'sort' => ['sometimes', 'string', 'max:64'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
