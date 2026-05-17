<?php

namespace App\Data\Orders;

use App\Enums\SubmissionStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * An order with its line items and snapshots. Reused across
 * custom-order, warehouse and other order-facing endpoints.
 *
 * `submission_status` is null when no submission row exists for the order
 * — frontend renders that as "Ny bestilling".
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
        public ?CustomerData $customer,
        public ?SubmissionStatus $submission_status,
        #[DataCollectionOf(OrderLineData::class)]
        public DataCollection $lines,
    ) {}

    public static function fromModel(Order $order): self
    {
        return new self(
            id: $order->id,
            wc_order_id: $order->wc_order_id,
            wc_modified_at: $order->wc_modified_at,
            customer: $order->customer ? CustomerData::fromModel($order->customer) : null,
            submission_status: $order->submission?->status,
            lines: OrderLineData::collect($order->lines, DataCollection::class),
        );
    }
}
