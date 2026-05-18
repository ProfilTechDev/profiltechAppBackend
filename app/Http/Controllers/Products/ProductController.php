<?php

namespace App\Http\Controllers\Products;

/**
 * General product-related endpoints — shared lookup data that multiple
 * frontends fetch once and cache.
 */
class ProductController
{
    /**
     * Static map of color slugs to hex values. Frontend caches this once
     * and joins against `raw_value` on order line attributes to render
     * a visual swatch.
     *
     * @return array<string, string>
     */
    public function colors(): array
    {
        return (array) config('custom_orders.colors', []);
    }
}
