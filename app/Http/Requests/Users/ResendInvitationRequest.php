<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `POST /users/{user}/resend-invitation`.
 */
class ResendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('resendInvitation', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
