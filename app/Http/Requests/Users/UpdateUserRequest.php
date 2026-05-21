<?php

namespace App\Http\Requests\Users;

use App\Authorization\Permissions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Payload for editing an existing user. All fields are `sometimes` so
 * the dialog can submit partial updates. Permissions, when present,
 * are treated as a complete replacement of the user's permission set.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(Permissions::keys())],
        ];
    }

    /**
     * @return array<int, string>|null
     */
    public function permissionsOrNull(): ?array
    {
        if (! $this->has('permissions')) {
            return null;
        }

        return (array) $this->validated('permissions', []);
    }
}
