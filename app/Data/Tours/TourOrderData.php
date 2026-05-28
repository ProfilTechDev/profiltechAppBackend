<?php

namespace App\Data\Tours;

use App\Data\Orders\OrderData;
use App\Models\TourOrder;
use Spatie\LaravelData\Data;

/**
 * A single slot on a tour: the display sequence ("2A") plus the
 * underlying order. Used by the tour-detail page to render the
 * ordered list of packages.
 */
class TourOrderData extends Data
{
    public function __construct(
        public int $primary_sequence,
        public int $insert_index,
        public string $display_sequence,
        public bool $added_after_approval,
        public ?OrderData $order,
    ) {}

    public static function fromModel(TourOrder $pivot): self
    {
        return new self(
            primary_sequence: $pivot->primary_sequence,
            insert_index: $pivot->insert_index,
            display_sequence: $pivot->display_sequence,
            added_after_approval: $pivot->added_after_approval,
            order: $pivot->order ? OrderData::fromModel($pivot->order) : null,
        );
    }
}
