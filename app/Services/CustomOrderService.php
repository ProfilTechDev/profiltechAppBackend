<?php

namespace App\Services;

use App\Data\CustomOrders\SubmissionLineData;
use App\Enums\SubmissionStatus;
use App\Jobs\CustomOrders\SendSubmissionJob;
use App\Models\Order;
use App\Models\OrderSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Domain logic for the custom-order workflow: saving the draft
 * vendor submission and finalising the send.
 */
class CustomOrderService
{
    /**
     * Load the order's submission with its lines eager-loaded, or null
     * if no submission has been started.
     */
    public function getSubmission(Order $order): ?OrderSubmission
    {
        return $order->submission()->with('lines')->first();
    }

    /**
     * Partially update an order's submission. Only the parts that are
     * passed in are touched — the rest is left as-is. Returns the
     * (created or updated) submission.
     *
     * @param  Collection<int, SubmissionLineData>|null  $lines  Replace lines if provided.
     * @param  array<string, string>  $providerFields  Provider/message fields to overwrite (subset of provider_id/subject/message).
     */
    public function updateSubmission(Order $order, ?Collection $lines = null, array $providerFields = []): OrderSubmission
    {
        return DB::transaction(function () use ($order, $lines, $providerFields): OrderSubmission {
            $submission = OrderSubmission::firstOrCreate(['order_id' => $order->id]);

            if ($lines !== null) {
                $this->replaceLines($submission, $order, $lines);
            }

            if ($providerFields !== []) {
                $submission->update($providerFields);
            }

            // A failed submission becomes editable again as a draft.
            if ($submission->status === SubmissionStatus::Failed) {
                $submission->update(['status' => SubmissionStatus::Draft]);
            }

            return $submission;
        });
    }

    /**
     * @param  Collection<int, SubmissionLineData>  $lines
     */
    private function replaceLines(OrderSubmission $submission, Order $order, Collection $lines): void
    {
        $submission->lines()->delete();
        $allowedLineIds = $order->lines()->pluck('id')->all();

        $lines
            ->filter(fn (SubmissionLineData $line): bool => $line->included)
            ->filter(fn (SubmissionLineData $line): bool => \in_array($line->id, $allowedLineIds, true))
            ->each(fn (SubmissionLineData $line) => $submission->lines()->create([
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
                'thickness' => $line->thickness,
            ]));
    }

    /**
     * Queue the send for the order's submission. The job sets `sent_at`
     * when the actual delivery succeeds (and retries up to 10 times on
     * upstream failures). Provider metadata must already be saved via
     * PATCH /submission before this is called.
     *
     * Throws a validation exception if the submission is incomplete.
     */
    public function sendSubmission(Order $order): OrderSubmission
    {
        $submission = $order->submission;

        if (! $submission || $submission->lines()->doesntExist()) {
            throw ValidationException::withMessages([
                'lines' => 'Cannot send — no submission lines have been saved yet.',
            ]);
        }

        $missing = collect(['provider_id', 'subject', 'message'])
            ->filter(fn (string $field): bool => empty($submission->{$field}))
            ->all();

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'provider' => 'Cannot send — missing provider field(s): '.implode(', ', $missing),
            ]);
        }

        $submission->update(['status' => SubmissionStatus::Queued]);
        SendSubmissionJob::dispatch($submission);

        return $submission;
    }
}
