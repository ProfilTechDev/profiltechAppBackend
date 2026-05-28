<?php

namespace App\Http\Requests\Fulfillment;

use App\Enums\PackingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Change an order's packing_status. `null` is valid only when the
 * order has no delivery_flow set — the service enforces the per-flow
 * whitelist on top of this loose check.
 */
class SetPackingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'packing_status' => [
                'nullable',
                Rule::enum(PackingStatus::class),
            ],
        ];
    }
}
