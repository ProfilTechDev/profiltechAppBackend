<?php

namespace App\Http\Filters\Fulfillment;

use App\Enums\ProductDepartment;
use App\Http\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[department]=steel_plates|flashings|accessories[,…]` —
 * matches orders that have at least one line snapshotted into the
 * requested department(s). Useful for the per-department picker views.
 */
class DepartmentFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        $allowed = array_column(ProductDepartment::cases(), 'value');

        $requested = array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $v): bool => in_array($v, $allowed, true),
        );

        if ($requested === []) {
            return;
        }

        $query->whereHas(
            'lines.snapshot',
            fn (Builder $q): Builder => $q->whereIn('department', $requested),
        );
    }
}
