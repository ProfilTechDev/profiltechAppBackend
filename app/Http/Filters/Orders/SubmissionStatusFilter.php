<?php

namespace App\Http\Filters\Orders;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * `?filter[status]=draft` — matches the order's submission status.
 * The pseudo-value `new` matches orders that have no submission at all.
 */
class SubmissionStatusFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $value = (string) $value;

        if ($value === 'new') {
            $query->whereDoesntHave('submission');
        } else {
            $query->whereHas('submission', fn (Builder $q) => $q->where('status', $value));
        }
    }
}
