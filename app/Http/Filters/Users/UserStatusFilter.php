<?php

namespace App\Http\Filters\Users;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * `?filter[status]=active|inactive|pending` — coarse status filter.
 *
 * - `active`   : is_active = true AND no pending invitation
 * - `inactive` : is_active = false
 * - `pending`  : has a non-accepted invitation (user hasn't completed
 *                their onboarding yet)
 */
class UserStatusFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        match ((string) $value) {
            'active' => $query
                ->where('is_active', true)
                ->whereDoesntHave('invitations', fn (Builder $q) => $q->whereNull('accepted_at')),
            'inactive' => $query->where('is_active', false),
            'pending' => $query->whereHas('invitations', fn (Builder $q) => $q->whereNull('accepted_at')),
            default => null,
        };
    }
}
