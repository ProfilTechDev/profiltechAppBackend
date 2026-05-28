<?php

namespace App\Http\Filters\Fulfillment;

use App\Http\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[on_tour]=yes|no|<tourId>` —
 *
 *   yes        → orders that are currently on any tour
 *   no         → orders not on any tour (used by the tour-picker)
 *   <integer>  → orders on exactly that tour
 */
class OnTourFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        if ($value === 'yes') {
            $query->has('tours');

            return;
        }

        if ($value === 'no') {
            $query->doesntHave('tours');

            return;
        }

        if (ctype_digit($value)) {
            $query->whereHas('tours', fn (Builder $q) => $q->where('tours.id', (int) $value));
        }
    }
}
