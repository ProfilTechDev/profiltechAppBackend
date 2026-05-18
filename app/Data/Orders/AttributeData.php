<?php

namespace App\Data\Orders;

use Spatie\LaravelData\Data;

/**
 * A single key/value configuration attribute on an order line.
 *
 * `raw_value` is WC's untouched meta value (typically a slug for taxonomy
 * attributes, or a scalar otherwise). Frontend can use it to join against
 * other lookups — fx the `/colors` map for visual swatches.
 */
class AttributeData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public string $value,
        public ?string $raw_value,
    ) {}
}
