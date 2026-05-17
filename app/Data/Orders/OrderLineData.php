<?php

namespace App\Data\Orders;

use App\Models\OrderLine;
use Spatie\LaravelData\Data;

/**
 * One line item of an order with its product snapshot.
 *
 * `name` and `is_custom` are read from the OrderLine's snapshot relation —
 * the transaction-time copy of the product, not its current state.
 */
class OrderLineData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $quantity,
        public bool $is_custom,
    ) {}

    public static function fromModel(OrderLine $line): self
    {
        return new self(
            id: $line->id,
            name: $line->snapshot?->name ?? '',
            quantity: $line->quantity,
            is_custom: $line->snapshot?->is_custom ?? false,
        );
    }
}
