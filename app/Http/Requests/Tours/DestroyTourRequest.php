<?php

namespace App\Http\Requests\Tours;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `DELETE /tours/{tour}`. TourService::deleteTour also
 * blocks deletion when the tour isn't a draft — that domain rule
 * lives in the service, not here.
 */
class DestroyTourRequest extends FormRequest
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
