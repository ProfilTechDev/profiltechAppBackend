<?php

namespace App\Data\WooCommerce;

use Spatie\LaravelData\Data;

/**
 * A single line item inside a WooCommerce order payload.
 */
class OrderLineData extends Data
{
    public function __construct(
        public int $product_id,
        public int $variation_id,
        public string $name,
        public int $quantity,
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
}
