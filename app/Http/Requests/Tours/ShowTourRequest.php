<?php

namespace App\Http\Requests\Tours;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `GET /tours/{tour}`. No body to validate — exists so the
 * future policy gate lives in the standard Laravel place rather than
 * inline in the controller. Open to any authenticated user today.
 */
class ShowTourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
