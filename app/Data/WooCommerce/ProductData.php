<?php

namespace App\Data\WooCommerce;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

/**
 * Typed representation of a WooCommerce product or variation payload.
 *
 * Maps the WC-side flag `is_custom_product` to our internal `is_custom`
 * (the "product"-suffix is implicit on the products table).
 */
class ProductData extends Data
{
    /**
     * @param  array<int, int>  $variations  Variation ids on a variable product; empty for simple/variation entries.
     */
    public function __construct(
        public int $id,
        public string $name,
        #[MapInputName('is_custom_product')]
        public bool $is_custom,
        public string $type = 'simple',
        public array $variations = [],
    ) {}
}
