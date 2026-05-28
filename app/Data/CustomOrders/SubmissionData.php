<?php

namespace App\Data\CustomOrders;

use App\Enums\SubmissionStatus;
use App\Models\OrderSubmission;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Current state of an order's vendor submission.
 */
class SubmissionData extends Data
{
    /**
     * @param  DataCollection<int, SubmissionLineData>  $lines
     */
    public function __construct(
        public SubmissionStatus $status,
        public ?string $provider_id,
        public ?string $subject,
        public ?string $message,
        public ?CarbonImmutable $sent_at,
        public ?CarbonImmutable $received_at,
        #[DataCollectionOf(SubmissionLineData::class)]
        public DataCollection $lines,
    ) {}

    public static function fromModel(OrderSubmission $submission): self
    {
        return new self(
            status: $submission->status,
            provider_id: $submission->provider_id,
            subject: $submission->subject,
            message: $submission->message,
            sent_at: $submission->sent_at,
            received_at: $submission->received_at,
            lines: SubmissionLineData::collect($submission->lines, DataCollection::class),
        );
    }
}
