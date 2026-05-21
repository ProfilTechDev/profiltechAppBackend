<?php

namespace App\Http\Requests\Users;

use App\Authorization\Permissions;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Payload for admin-initiated user creation. Always triggers an
 * invitation email — there is no "create user with password" flow in
 * v1; admins invite, the invitee sets their own password.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(Permissions::keys())],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return (array) ($this->validated('permissions') ?? []);
    }
}
