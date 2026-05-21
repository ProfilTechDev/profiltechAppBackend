<?php

namespace App\Http\Filters\Orders;

use App\Http\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[order_status]=active|completed` — coarse filter on the
 * WooCommerce order status synced into `orders.status`:
 *
 *   active     → anything that is not `completed` (typically
 *                processing/on-hold/pending/refunded/cancelled —
 *                the list UI lumps them together as "open")
 *   completed  → only WC orders that have been marked Completed
 *
 * Finer-grained filtering on individual WC statuses isn't needed by
 * the UI today; add cases here if that changes.
 */
class OrderStatusFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        if ($value === 'completed') {
            $query->where('status', 'completed');

            return;
        }

        if ($value === 'active') {
            $query->where('status', '!=', 'completed');
        }
    }
}
