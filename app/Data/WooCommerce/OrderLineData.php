<?php

namespace App\Data\WooCommerce;

use Spatie\LaravelData\Data;

/**
 * A single line item inside a WooCommerce order payload.
 */
class OrderLineData extends Data
{
    /**
     * @param  array<int, array{key?: string, value?: mixed, display_key?: string, display_value?: mixed}>  $meta_data
     */
    public function __construct(
        public int $product_id,
        public int $variation_id,
        public string $name,
        public int $quantity,
        public array $meta_data = [],
    ) {}

    /**
     * The WC id of the sellable unit this line refers to:
     * the variation when set, otherwise the parent product.
     */
    public function sellableWcId(): int
    {
        return $this->variation_id > 0 ? $this->variation_id : $this->product_id;
    }

    /**
     * The parent product id if this line is for a variation, otherwise null.
     */
    public function parentWcId(): ?int
    {
        return $this->variation_id > 0 ? $this->product_id : null;
    }

    /**
     * Customer-visible attributes from the line's meta_data. Underscore-
     * prefixed keys are WC-internal (totals, tax, etc.) and skipped.
     *
     * Returns an ordered list preserving WC's order.
     *
     * @return array<int, array{key: string, label: string, value: string}>
     */
    public function extractAttributes(): array
    {
        $attributes = [];

        foreach ($this->meta_data as $meta) {
            $key = (string) ($meta['key'] ?? '');

            if ($key === '' || str_starts_with($key, '_')) {
                continue;
            }

            $attributes[] = [
                'key' => $key,
                'label' => (string) ($meta['display_key'] ?? $key),
                'value' => $this->stringifyValue($meta['display_value'] ?? $meta['value'] ?? ''),
                'raw_value' => $this->stringifyValue($meta['value'] ?? ''),
            ];
        }

        return $attributes;
    }

    private function stringifyValue(mixed $value): string
    {
        if (\is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }
}
