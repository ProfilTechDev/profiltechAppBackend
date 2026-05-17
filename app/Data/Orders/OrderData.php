<?php

namespace App\Data\Orders;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * An order with its line items and snapshots. Reused across
 * custom-order, warehouse and other order-facing endpoints.
 */
class OrderData extends Data
{
    /**
     * @param  DataCollection<int, OrderLineData>  $lines
     */
    public function __construct(
        public int $id,
        public int $wc_order_id,
        public ?CarbonImmutable $wc_modified_at,
        #[DataCollectionOf(OrderLineData::class)]
        public DataCollection $lines,
    ) {}
}
