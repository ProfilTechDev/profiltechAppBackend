<?php

namespace App\Data\Orders;

use App\Enums\TourStatus;
use App\Models\TourOrder;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * Lightweight pointer to the tour an order is currently assigned to,
 * with the per-order display sequence (e.g. "2A"). Nested into
 * OrderData so the planning list can show "On Tour Foo · 2A".
 */
class TourAssignmentData extends Data
{
    public function __construct(
        public int $tour_id,
        public string $tour_name,
        public CarbonImmutable $tour_date,
        public TourStatus $tour_status,
        public string $display_sequence,
        public int $primary_sequence,
        public int $insert_index,
        public bool $added_after_approval,
    ) {}

    public static function fromPivot(TourOrder $pivot): self
    {
        $pivot->loadMissing('tour');

        return new self(
            tour_id: $pivot->tour_id,
            tour_name: (string) $pivot->tour?->name,
            tour_date: $pivot->tour?->tour_date ?? CarbonImmutable::now(),
            tour_status: $pivot->tour?->status ?? TourStatus::Draft,
            display_sequence: $pivot->display_sequence,
            primary_sequence: $pivot->primary_sequence,
            insert_index: $pivot->insert_index,
            added_after_approval: $pivot->added_after_approval,
        );
    }
}
