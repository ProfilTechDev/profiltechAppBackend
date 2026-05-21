<?php

namespace App\Listeners\CustomOrders\SubmissionSendFailed;

use App\Events\CustomOrders\SubmissionSendFailed;
use Illuminate\Support\Facades\Log;

/**
 * Records each failed attempt at WARNING level so container logs
 * show retry context. Permanent failure (after retries exhausted)
 * is handled separately by `LogPermanentFailure` listening on
 * `SubmissionStatusUpdated` — this listener only describes one
 * attempt, not the final verdict.
 */
class LogAttemptFailure
{
    public function handle(SubmissionSendFailed $event): void
    {
        $context = [
            'submission_id' => $event->submission->id,
            'order_id' => $event->submission->order_id,
            'provider_id' => $event->submission->provider_id,
            'exception' => $event->exception::class,
            'error' => $event->exception->getMessage(),
        ];

        if ($event->attempt !== null) {
            $context['attempt'] = $event->attempt;
        }

        if ($event->willRetry !== null) {
            $context['will_retry'] = $event->willRetry;
        }

        Log::warning('Submission send attempt failed', $context);
    }
}
