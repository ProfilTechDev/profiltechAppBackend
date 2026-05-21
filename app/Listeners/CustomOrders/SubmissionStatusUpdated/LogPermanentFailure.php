<?php

namespace App\Listeners\CustomOrders\SubmissionStatusUpdated;

use App\Enums\SubmissionStatus;
use App\Events\CustomOrders\SubmissionStatusUpdated;
use Illuminate\Support\Facades\Log;

/**
 * Writes a CRITICAL log line whenever a submission transitions to
 * Failed (i.e. after the queued send job has exhausted all retries).
 * Other transitions are ignored — the listener is event-scoped via
 * the typehint and status-scoped via the early return.
 */
class LogPermanentFailure
{
    public function handle(SubmissionStatusUpdated $event): void
    {
        if ($event->submission->status !== SubmissionStatus::Failed) {
            return;
        }

        Log::critical('Submission send permanently failed after all retries', [
            'submission_id' => $event->submission->id,
            'order_id' => $event->submission->order_id,
            'provider_id' => $event->submission->provider_id,
            'previous_status' => $event->previousStatus?->value,
            'error' => $event->reason,
        ]);
    }
}
