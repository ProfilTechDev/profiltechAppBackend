<?php

namespace App\Http\Filters\Users;

use App\Authorization\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * `?filter[permission]=warehouse.access` — filter users that have the
 * given permission assigned directly. Unknown permission keys are
 * silently ignored.
 */
class UserPermissionFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $permission = (string) $value;

        if (! in_array($permission, Permissions::keys(), true)) {
            return;
        }

        $query->whereHas('permissions', fn (Builder $p) => $p->where('name', $permission));
    }
}
