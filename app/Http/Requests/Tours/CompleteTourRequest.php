<?php

namespace App\Http\Requests\Tours;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `POST /tours/{tour}/complete`. The approved → completed
 * status guard lives in TourService::completeTour.
 */
class CompleteTourRequest extends FormRequest
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
