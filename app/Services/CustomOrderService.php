<?php

namespace App\Services;

use App\Data\CustomOrders\SubmissionLineData;
use App\Enums\SubmissionStatus;
use App\Events\CustomOrders\AfterSuccessfulSubmissionSend;
use App\Events\CustomOrders\BeforeSubmissionSend;
use App\Events\CustomOrders\SubmissionSendFailed;
use App\Events\CustomOrders\SubmissionStatusUpdated;
use App\Exceptions\CustomOrders\MissingProviderEmailException;
use App\Jobs\CustomOrders\SendSubmissionJob;
use App\Mail\CustomOrders\SubmissionMail;
use App\Models\Order;
use App\Models\OrderSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Domain logic for the custom-order workflow: saving the draft
 * vendor submission and finalising the send.
 */
class CustomOrderService
{
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

        $this->markSubmissionQueued($submission);
        SendSubmissionJob::dispatch($submission);

        return $submission;
    }

    /**
     * Run the actual delivery of a submission: validate provider email,
     * send the mail, transition state to Sent, and dispatch lifecycle
     * events along the way. Throws on failure so the caller (the queued
     * job, a manual resend controller, a CLI command, …) can react.
     *
     * `$attempt` and `$willRetry` are queue-context that callers can
     * pass through — they end up on the lifecycle events but the
     * service itself doesn't act on them. Pass null for non-queue
     * callers.
     */
    public function deliverSubmission(
        OrderSubmission $submission,
        ?int $attempt = null,
        ?bool $willRetry = null,
    ): void {
        BeforeSubmissionSend::dispatch($submission, $attempt);

        try {
            $providerEmail = (string) ($this->providerConfig($submission->provider_id)['email'] ?? '');

            if ($providerEmail === '') {
                throw new MissingProviderEmailException($submission->provider_id);
            }

            Mail::send(new SubmissionMail($submission));

            $this->markSubmissionSent($submission);

            AfterSuccessfulSubmissionSend::dispatch($submission);
        } catch (Throwable $e) {
            SubmissionSendFailed::dispatch($submission, $e, $attempt, $willRetry);

            // Re-throw so the caller's retry/failure flow runs. The
            // queue worker uses this to increment the attempt counter
            // and eventually trigger `SendSubmissionJob::failed()`.
            throw $e;
        }
    }

    /**
     * Provider config for a given provider_id. Wrapped in a helper so
     * the dynamic key lookup is hidden from IDE inspections —
     * `config('custom_orders.providers')` resolves to a literal path
     * that Laravel-aware static analysis can validate.
     *
     * @return array<string, mixed>
     */
    private function providerConfig(string $providerId): array
    {
        $providers = (array) config('custom_orders.providers');

        return (array) ($providers[$providerId] ?? []);
    }

    /**
     * Transition a submission to Queued. Called from `sendSubmission`
     * after validation; dispatches the domain event so listeners (audit
     * log, broadcast, …) can react.
     */
    public function markSubmissionQueued(OrderSubmission $submission): void
    {
        $this->transitionTo($submission, SubmissionStatus::Queued);
    }

    /**
     * Transition a submission to Sent. Called from the queued job once
     * the upstream mail delivery succeeds. Stamps `sent_at` so the UI
     * can show when it actually left.
     */
    public function markSubmissionSent(OrderSubmission $submission): void
    {
        $this->transitionTo($submission, SubmissionStatus::Sent, ['sent_at' => now()]);
    }

    /**
     * Transition a submission to Failed. Called from the queued job's
     * `failed()` hook after all retries are exhausted. The `$reason`
     * (typically an exception message) is forwarded on the event so
     * listeners can log/audit why it failed.
     */
    public function markSubmissionFailed(OrderSubmission $submission, ?string $reason = null): void
    {
        $this->transitionTo($submission, SubmissionStatus::Failed, reason: $reason);
    }

    /**
     * Apply a status change + any extra columns in a single update, then
     * dispatch `SubmissionStatusUpdated` carrying the previous status so
     * listeners can render a transition (e.g. "queued → sent"). `$reason`
     * is free-text context attached to the event for audit purposes.
     *
     * @param  array<string, mixed>  $extra
     */
    private function transitionTo(
        OrderSubmission $submission,
        SubmissionStatus $newStatus,
        array $extra = [],
        ?string $reason = null,
    ): void {
        $previous = $submission->status;

        $submission->update(['status' => $newStatus, ...$extra]);

        SubmissionStatusUpdated::dispatch($submission, $previous, $reason);
    }
}
