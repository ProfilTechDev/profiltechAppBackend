<?php

namespace App\Http\Filters\Users;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * `?filter[search]=mattias` — matches user name OR email.
 */
class UserSearchFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $needle = '%'.(string) $value.'%';

        $query->where(function (Builder $q) use ($needle): void {
            $q->where('name', 'like', $needle)
                ->orWhere('email', 'like', $needle);
        });
    }
}
