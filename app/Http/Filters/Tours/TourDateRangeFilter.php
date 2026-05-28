<?php

namespace App\Http\Filters\Tours;

use App\Http\Filters\Filter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[date_range]=YYYY-MM-DD..YYYY-MM-DD` — inclusive range on
 * `tours.tour_date`. Open-ended ranges are supported with an empty
 * side (`2026-05-01..` or `..2026-06-30`). Malformed input is silently
 * ignored — list endpoints don't 422 on a stale URL.
 */
class TourDateRangeFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        [$from, $to] = array_pad(explode('..', $value, 2), 2, '');

        $fromDate = $this->parse($from);
        $toDate = $this->parse($to);

        if ($fromDate !== null) {
            $query->where('tour_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->where('tour_date', '<=', $toDate);
        }
    }

    private function parse(string $value): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
