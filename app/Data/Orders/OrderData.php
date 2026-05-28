<?php

namespace App\Data\Orders;

use App\Enums\DeliveryFlow;
use App\Enums\PackingStatus;
use App\Enums\SubmissionStatus;
use App\Models\Order;
use App\Models\TourOrder;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * An order with its line items and snapshots. Reused across
 * custom-order, warehouse and planning endpoints.
 *
 * Planning-specific fields (`delivery_flow`, `packing_ready_at`,
 * `shipping_address`, `tour_assignment`) are null on responses where
 * they aren't relevant — they're populated when the source model has
 * the relevant relations eager-loaded.
 *
 * `submission_status` is null when no submission row exists — frontend
 * renders that as "Ny bestilling".
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
        public ?CarbonImmutable $submission_received_at,
        public ?DeliveryFlow $delivery_flow,
        public ?PackingStatus $packing_status,
        public ?CarbonImmutable $packing_ready_at,
        public ?ShippingAddressData $shipping_address,
        public ?TourAssignmentData $tour_assignment,
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
            submission_received_at: $order->submission?->received_at,
            delivery_flow: $order->delivery_flow,
            packing_status: $order->packing_status,
            packing_ready_at: $order->packing_ready_at,
            shipping_address: $order->shippingAddress
                ? ShippingAddressData::fromModel($order->shippingAddress)
                : null,
            tour_assignment: self::currentTourAssignment($order),
            lines: OrderLineData::collect($order->lines, DataCollection::class),
        );
    }

    /**
     * Resolve the order's most-recent open tour assignment, if any. The
     * pivot must be eager-loaded by the caller (`tours` relation) for
     * this to surface without N+1.
     */
    private static function currentTourAssignment(Order $order): ?TourAssignmentData
    {
        if (! $order->relationLoaded('tours') || $order->tours->isEmpty()) {
            return null;
        }

        $tour = $order->tours->first();

        $pivot = $tour->pivot ?? null;

        if (! $pivot instanceof TourOrder) {
            return null;
        }

        $pivot->setRelation('tour', $tour);

        return TourAssignmentData::fromPivot($pivot);
    }
}
