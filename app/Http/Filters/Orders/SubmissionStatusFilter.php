<?php

namespace App\Http\Filters\Orders;

use App\Enums\SubmissionStatus;
use App\Http\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[submission_status]=sent|unsent` — coarse status-filter
 * tailored for the custom-orders list UI. Maps to the underlying
 * `order_submissions` table:
 *
 *   sent    → submission exists and status === Sent
 *   unsent  → no submission row, OR submission with any non-Sent
 *             status (draft / queued / failed)
 */
class SubmissionStatusFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        if ($value === 'sent') {
            $query->whereHas(
                'submission',
                fn (Builder $q) => $q->where('status', SubmissionStatus::Sent->value),
            );

            return;
        }

        if ($value === 'unsent') {
            $query->where(function (Builder $q): void {
                $q->whereDoesntHave('submission')
                    ->orWhereHas(
                        'submission',
                        fn (Builder $sq) => $sq->where('status', '!=', SubmissionStatus::Sent->value),
                    );
            });
        }
    }
}
