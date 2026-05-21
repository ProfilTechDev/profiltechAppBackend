<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Base class for query-string-driven filters used by `App\Queries\*`.
 *
 * Centralises the "skip when value is null or empty" contract so each
 * concrete filter can focus on its own logic — implement `apply()`
 * with the value already cast to a non-empty string.
 *
 * Concrete usage from a query method:
 *
 *     (new OrderStatusFilter)($query, $value);
 *
 * The `__invoke` entry point does the null/empty short-circuit and
 * then delegates to `apply()`.
 */
abstract class Filter
{
    final public function __invoke(Builder $query, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $value = (string) $value;

        if ($value === '') {
            return;
        }

        $this->apply($query, $value);
    }

    /**
     * Apply the filter. Only called when `$value` is a non-empty string.
     */
    abstract protected function apply(Builder $query, string $value): void;
}
