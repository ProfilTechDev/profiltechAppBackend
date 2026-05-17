<?php

namespace App\Data\CustomOrders;

use App\Models\OrderSubmissionLine;
use Spatie\LaravelData\Data;

/**
 * Describes how a single order line should be sent to the vendor.
 *
 * Used both as input (PATCH /custom-orders/{order} payload) and output
 * (when reading back the saved submission). On output `included` is
 * always true — only stored rows are returned.
 */
class SubmissionLineData extends Data
{
    public function __construct(
        public int $id,
        public int $quantity,
        public bool $included,
        public ?float $thickness,
    ) {}

    public static function fromModel(OrderSubmissionLine $line): self
    {
        return new self(
            id: $line->order_line_id,
            quantity: $line->quantity,
            included: true,
            thickness: $line->thickness !== null ? (float) $line->thickness : null,
        );
    }
}
