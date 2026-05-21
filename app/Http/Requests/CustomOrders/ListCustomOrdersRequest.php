<?php

namespace App\Http\Requests\CustomOrders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates query-string params for the paginated custom-orders list.
 * Auth is enforced by Sanctum middleware on the route; no additional
 * policy gate today (any logged-in staff user can list).
 *
 * Sort is loosely validated (max length only) — the query class owns
 * the whitelist of allowed sort fields so we don't duplicate it.
 */
class ListCustomOrdersRequest extends FormRequest
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
            'filter.order_status' => ['nullable', 'in:active,completed'],
            'filter.submission_status' => ['nullable', 'in:sent,unsent'],
            'filter.search' => ['nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'max:64'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
