<?php

namespace App\Http\Controllers;

use App\Queries\ProductQuery;

/**
 * General product-related endpoints — shared lookup data that multiple
 * frontends fetch once and cache. Reads go through `ProductQuery`.
 */
class ProductController
{
    public function __construct(
        private readonly ProductQuery $query,
    ) {}

    /**
     * @return array<string, string>
     */
    public function colors(): array
    {
        return $this->query->colors();
    }
}
