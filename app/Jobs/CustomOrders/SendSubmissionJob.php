<?php

namespace App\Jobs\CustomOrders;

use App\Enums\SubmissionStatus;
use App\Events\CustomOrders\SubmissionStatusUpdated;
use App\Mail\CustomOrders\SubmissionMail;
use App\Models\OrderSubmission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Sends an order submission to the chosen provider.
 *
 * Tolerates upstream rate limits (e.g. Google's per-minute caps) with a
 * generous retry policy: up to 10 attempts spaced 10 minutes apart.
 * On success, stamps `sent_at` on the submission so the UI reflects it.
 */
class SendSubmissionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $backoff = 600;

    public function __construct(public readonly OrderSubmission $submission) {}

    public function handle(): void
    {
        Log::info('Submission send attempt starting', [
            'submission_id' => $this->submission->id,
            'order_id' => $this->submission->order_id,
            'provider_id' => $this->submission->provider_id,
            'attempt' => $this->attempts(),
        ]);

        try {
            $providerEmail = (string) ($this->providerConfig()['email'] ?? '');

            if ($providerEmail === '') {
                throw new RuntimeException(
                    "Missing email for provider '{$this->submission->provider_id}'. Check config/custom_orders.php and .env.",
                );
            }

            Mail::send(new SubmissionMail($this->submission));

            $this->submission->update([
                'status' => SubmissionStatus::Sent,
                'sent_at' => now(),
            ]);

            Log::info('Broadcast: about to dispatch', [
                'submission_id' => $this->submission->id,
                'broadcaster' => config('broadcasting.default'),
                'reverb_host' => config('broadcasting.connections.reverb.options.host'),
                'reverb_port' => config('broadcasting.connections.reverb.options.port'),
                'reverb_app_id_set' => (bool) config('broadcasting.connections.reverb.app_id'),
                'channel' => "orders.{$this->submission->order_id}.submission",
            ]);

            try {
                SubmissionStatusUpdated::dispatch($this->submission);
                Log::info('Broadcast: dispatch returned without throwing', [
                    'submission_id' => $this->submission->id,
                ]);
            } catch (Throwable $broadcastError) {
                Log::error('Broadcast: dispatch threw', [
                    'submission_id' => $this->submission->id,
                    'error' => $broadcastError->getMessage(),
                    'trace' => $broadcastError->getTraceAsString(),
                ]);
            }

            Log::info('Submission send succeeded', [
                'submission_id' => $this->submission->id,
                'sent_at' => $this->submission->sent_at?->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            // Status stays 'queued' — only failed() (after all retries
            // are exhausted) flips it to 'failed'.
            Log::warning('Submission send attempt failed, will retry', [
                'submission_id' => $this->submission->id,
                'attempt' => $this->attempts(),
                'remaining' => $this->tries - $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->submission->update(['status' => SubmissionStatus::Failed]);

        SubmissionStatusUpdated::dispatch($this->submission);

        Log::critical('Submission send permanently failed after all retries', [
            'submission_id' => $this->submission->id,
            'order_id' => $this->submission->order_id,
            'provider_id' => $this->submission->provider_id,
            'attempts' => $this->tries,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Provider config (email, language, …) for this submission. Wrapped
     * in a helper so the dynamic key lookup is hidden from IDE
     * inspections — `config('custom_orders.providers')` resolves to the
     * literal path that the Laravel plugin can validate.
     *
     * @return array<string, mixed>
     */
    private function providerConfig(): array
    {
        $providers = (array) config('custom_orders.providers');

        return (array) ($providers[$this->submission->provider_id] ?? []);
    }

    /**
     * Prevent the same submission from being sent twice concurrently
     * (e.g. if a manual re-send is triggered while a queued attempt is
     * still pending).
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("submission:{$this->submission->id}"))
                ->releaseAfter(60)
                ->expireAfter(7200),
        ];
    }
}
