<?php

namespace App\Queries;

/**
 * Read-side for product lookup data. Currently config-backed only;
 * if/when products become a queryable domain model with filters,
 * methods here will return paginated lists analogous to
 * `CustomOrderQuery::list()`.
 */
class ProductQuery
{
    /**
     * Static map of color slugs → hex values. Frontend caches this
     * once and joins against `raw_value` on order line attributes to
     * render a visual swatch.
     *
     * @return array<string, string>
     */
    public function colors(): array
    {
        return (array) config('custom_orders.colors', []);
    }
}
