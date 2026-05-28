<?php

namespace App\Http\Requests\Tours;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `POST /tours/{tour}/approve`. The draft → approved
 * status guard lives in TourService::approveTour.
 */
class ApproveTourRequest extends FormRequest
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
