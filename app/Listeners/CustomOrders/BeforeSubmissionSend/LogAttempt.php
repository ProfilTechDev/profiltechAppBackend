<?php

namespace App\Listeners\CustomOrders\BeforeSubmissionSend;

use App\Events\CustomOrders\BeforeSubmissionSend;
use Illuminate\Support\Facades\Log;

/**
 * Records each send attempt at INFO level so container logs show when
 * the queued job picks up a submission and which retry it's on.
 */
class LogAttempt
{
    public function handle(BeforeSubmissionSend $event): void
    {
        $context = [
            'submission_id' => $event->submission->id,
            'order_id' => $event->submission->order_id,
            'provider_id' => $event->submission->provider_id,
        ];

        if ($event->attempt !== null) {
            $context['attempt'] = $event->attempt;
        }

        Log::info('Submission send attempt starting', $context);
    }
}
