<?php

namespace App\Http\Filters\Tours;

use App\Enums\TourStatus;
use App\Http\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[status]=draft|approved|completed` — exact match on
 * `tours.status`. Multiple values can be passed comma-separated
 * (`draft,approved`) for the "active tours" list view.
 */
class TourStatusFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        $allowed = array_column(TourStatus::cases(), 'value');

        $requested = array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $v): bool => in_array($v, $allowed, true),
        );

        if ($requested === []) {
            return;
        }

        $query->whereIn('status', $requested);
    }
}
