<?php

namespace App\Data\WooCommerce;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Typed representation of a WooCommerce order payload.
 *
 * Field names match the WooCommerce REST API so payloads can be hydrated
 * directly with OrderData::from($payload).
 */
class OrderData extends Data
{
    /**
     * @param  DataCollection<int, OrderLineData>  $line_items
     */
    public function __construct(
        public int $id,
        #[WithCast(DateTimeInterfaceCast::class, format: ['Y-m-d\TH:i:s', DATE_ATOM])]
        public ?CarbonImmutable $date_modified_gmt,
        public AddressData $billing,
        public AddressData $shipping,
        #[DataCollectionOf(OrderLineData::class)]
        public DataCollection $line_items,
    ) {}
}
