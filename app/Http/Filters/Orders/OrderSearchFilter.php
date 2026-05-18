<?php

namespace App\Http\Filters\Orders;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * `?filter[search]=mattias` — matches WC order id, WC display number,
 * or the customer's first/last/full name.
 */
class OrderSearchFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $needle = '%'.(string) $value.'%';

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
