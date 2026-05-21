<?php

namespace App\Http\Filters\Orders;

use App\Http\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[search]=mattias` — matches WC order id, WC display number,
 * or the customer's first/last/full name.
 */
class OrderSearchFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        $needle = '%'.$value.'%';

        $query->where(function (Builder $q) use ($needle): void {
            $q->where('wc_order_id', 'like', $needle)
                ->orWhere('wc_number', 'like', $needle)
                ->orWhereHas('customer', function (Builder $c) use ($needle): void {
                    $c->where('first_name', 'like', $needle)
                        ->orWhere('last_name', 'like', $needle)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$needle]);
                });
        });
    }
}
