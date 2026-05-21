<?php

namespace App\Http\Filters\Users;

use App\Http\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[search]=mattias` — matches user name OR email.
 */
class UserSearchFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        $needle = '%'.$value.'%';

        $query->where(function (Builder $q) use ($needle): void {
            $q->where('name', 'like', $needle)
                ->orWhere('email', 'like', $needle);
        });
    }
}
