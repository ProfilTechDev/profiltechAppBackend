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
        public ?string $wc_number,
        public string $status,
        public string $currency,
        public string $total,
        public ?string $payment_method_title,
        public ?string $customer_note,
        public ?CarbonImmutable $date_created,
        public ?CarbonImmutable $date_paid,
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
            wc_number: $order->wc_number,
            status: $order->status,
            currency: $order->currency,
            total: (string) $order->total,
            payment_method_title: $order->payment_method_title,
            customer_note: $order->customer_note,
            date_created: $order->date_created,
            date_paid: $order->date_paid,
            wc_modified_at: $order->wc_modified_at,
            customer: $order->customer ? CustomerData::fromModel($order->customer) : null,
            submission_status: $order->submission?->status,
            lines: OrderLineData::collect($order->lines, DataCollection::class),
        );
    }
}
