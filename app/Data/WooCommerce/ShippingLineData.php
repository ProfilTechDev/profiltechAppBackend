<?php

namespace App\Data\WooCommerce;

use Spatie\LaravelData\Data;

/**
 * One shipping line as it appears under `shipping_lines` in a WC
 * order payload. We don't bind WC's full schema — only the fields
 * we need to populate an `OrderShipment`.
 *
 * `_pft_rate_id` is the Table Rate row id our WP-side mu-plugin
 * persists on the shipping item. Missing for non-Table-Rate methods
 * (Shipmondo, free_shipping) — those are categorised via
 * `instance_id` / `method_id` fallback.
 */
class ShippingLineData extends Data
{
    /**
     * @param  array<int, array{id?: int, key?: string, value?: mixed}>  $meta_data
     */
    public function __construct(
        public int $id,
        public string $method_title,
        public string $method_id,
        public ?int $instance_id,
        public string $total,
        public array $meta_data = [],
    ) {}

    /**
     * Extract `_pft_rate_id` from the meta_data bag. Returns null if
     * the WP-side mu-plugin didn't fire (e.g. orders placed before
     * the snippet was installed, or Shipmondo orders where there's
     * no rate_id to capture).
     */
    public function rateId(): ?int
    {
        foreach ($this->meta_data as $meta) {
            if (($meta['key'] ?? null) === '_pft_rate_id') {
                $value = $meta['value'] ?? null;

                return $value === null || $value === '' ? null : (int) $value;
            }
        }

        return null;
    }
}
