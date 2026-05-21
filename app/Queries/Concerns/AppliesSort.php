<?php

namespace App\Queries\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared sort handling for query classes. Validates the requested sort
 * field against a whitelist; silently falls back to a default when the
 * requested field isn't allowed.
 *
 * Consumers pass:
 *   - the Eloquent builder being composed
 *   - the requested sort string (e.g. `name`, `-created_at`)
 *   - the list of allowed fields (typically a class constant)
 *   - the default field used when the requested one is unknown
 *
 * The leading-`-` prefix toggles descending order, mirroring the
 * conventional URL syntax `?sort=-created_at`.
 */
trait AppliesSort
{
    /**
     * @param  array<int, string>  $allowed
     */
    protected function applySort(Builder $query, string $sort, array $allowed, string $default): void
    {
        $field = ltrim($sort, '-');

        if (! \in_array($field, $allowed, true)) {
            $field = $default;
            $sort = $default;
        }

        $query->orderBy($field, str_starts_with($sort, '-') ? 'desc' : 'asc');
    }
}
