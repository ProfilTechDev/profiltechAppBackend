<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `GET /users/{user}`. No body to validate — exists so the
 * policy check lives in the standard Laravel place (alongside other
 * user-action FormRequests) rather than inline in the controller.
 */
class ShowUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
