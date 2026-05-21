<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `POST /users/{user}/deactivate`. UserPolicy::deactivate
 * also blocks self-deactivation.
 */
class DeactivateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('deactivate', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
