<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates query-string params for the paginated user list. Sort is
 * intentionally loosely validated (max length only) — the query
 * class owns the whitelist of allowed sort fields so we don't
 * duplicate it here.
 */
class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.search' => ['nullable', 'string', 'max:255'],
            'filter.status' => ['nullable', 'in:active,inactive,pending'],
            'filter.permission' => ['nullable', 'string', 'max:64'],
            'sort' => ['sometimes', 'string', 'max:64'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
