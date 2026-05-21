<?php

namespace App\Http\Filters\Users;

use App\Authorization\Permissions;
use App\Http\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[permission]=warehouse.access` — filter users that have the
 * given permission assigned directly. Unknown permission keys are
 * silently ignored.
 */
class UserPermissionFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        if (! in_array($value, Permissions::keys(), true)) {
            return;
        }

        $query->whereHas('permissions', fn (Builder $p) => $p->where('name', $value));
    }
}
