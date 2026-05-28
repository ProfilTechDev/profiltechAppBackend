<?php

namespace App\Http\Requests\Tours;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorises `DELETE /tours/{tour}/orders/{order}`. The "cannot
 * remove from a completed tour" guard lives in
 * TourService::removeOrderFromTour.
 */
class RemoveOrderFromTourRequest extends FormRequest
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
