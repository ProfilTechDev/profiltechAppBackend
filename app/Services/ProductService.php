<?php

namespace App\Services;

use App\Enums\ProductDepartment;
use App\Models\Product;

/**
 * Domain operations on the Product model. Today the only mutation is
 * a backfill helper — products are otherwise written exclusively by
 * `WooCommerceService` during the WC sync.
 */
class ProductService
{
    /**
     * Backfill `department` on a product that has none. Skips event
     * dispatch (there is no domain event for this yet). No-op when
     * the column is already set so re-runs don't overwrite values
     * resolved by the sync.
     */
    public function backfillDepartment(Product $product, ProductDepartment $department): void
    {
        if ($product->department !== null) {
            return;
        }

        $product->update(['department' => $department]);
    }
}
