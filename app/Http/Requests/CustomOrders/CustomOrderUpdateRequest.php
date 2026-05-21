<?php

namespace App\Http\Requests\CustomOrders;

use App\Data\CustomOrders\SubmissionLineData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

/**
 * Partial update of an order's submission. The payload may contain
 * `lines` (Step 1), provider fields (Step 2), or both. Anything not
 * sent is left untouched.
 */
class CustomOrderUpdateRequest extends FormRequest
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
            'lines' => ['sometimes', 'array'],
            'lines.*.id' => ['required_with:lines', 'integer'],
            'lines.*.quantity' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.included' => ['required_with:lines', 'boolean'],
            'lines.*.thickness' => ['nullable', 'numeric'],

            'provider_id' => ['sometimes', 'string'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'string'],
        ];
    }

    /**
     * @return Collection<int, SubmissionLineData>|null
     */
    public function lines(): ?Collection
    {
        if (! $this->has('lines')) {
            return null;
        }

        return collect($this->validated('lines'))
            ->map(fn (array $line): SubmissionLineData => SubmissionLineData::from($line));
    }

    /**
     * @return array<string, string> Only the provider fields that were sent.
     */
    public function providerFields(): array
    {
        return array_intersect_key(
            $this->validated(),
            array_flip(['provider_id', 'subject', 'message']),
        );
    }
}
