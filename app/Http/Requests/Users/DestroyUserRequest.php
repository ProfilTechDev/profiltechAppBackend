<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `DELETE /users/{user}`. UserPolicy::delete also blocks
 * self-deletion (a user can't delete themselves) — that check lives
 * in the policy, not here.
 */
class DestroyUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
