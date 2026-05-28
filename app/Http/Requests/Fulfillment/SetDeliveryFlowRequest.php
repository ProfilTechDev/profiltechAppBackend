<?php

namespace App\Http\Requests\Fulfillment;

use App\Enums\DeliveryFlow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Change (or clear) an order's delivery_flow. `null` is a valid value
 * — it sends the order back to the "Til planlægning" bucket.
 */
class SetDeliveryFlowRequest extends FormRequest
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
            'delivery_flow' => [
                'nullable',
                Rule::enum(DeliveryFlow::class),
            ],
        ];
    }
}
