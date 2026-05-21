<?php

namespace App\Listeners\CustomOrders\AfterSuccessfulSubmissionSend;

use App\Events\CustomOrders\AfterSuccessfulSubmissionSend;
use Illuminate\Support\Facades\Log;

/**
 * Records successful sends at INFO level so container logs reflect
 * the full lifecycle (attempt → success) alongside the failure log
 * written by `LogPermanentFailure`.
 */
class LogSuccess
{
    public function handle(AfterSuccessfulSubmissionSend $event): void
    {
        Log::info('Submission send succeeded', [
            'submission_id' => $event->submission->id,
            'sent_at' => $event->submission->sent_at?->toIso8601String(),
        ]);
    }
}
