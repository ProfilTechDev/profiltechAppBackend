<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `POST /users/{user}/reactivate`. Uses the generic
 * `update` ability — reactivation is just flipping `is_active` back
 * on, no separate policy method needed.
 */
class ReactivateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
