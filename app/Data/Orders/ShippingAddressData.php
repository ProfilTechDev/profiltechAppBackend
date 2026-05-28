<?php

namespace App\Data\Orders;

use App\Models\OrderAddress;
use Spatie\LaravelData\Data;

/**
 * Shipping address snapshot. Used by the planning UI to render the
 * map-card label (name + city) and the per-order address block on the
 * tour-detail page.
 */
class ShippingAddressData extends Data
{
    public function __construct(
        public string $name,
        public ?string $company,
        public string $address_1,
        public ?string $address_2,
        public string $city,
        public string $postcode,
        public string $country,
        public ?string $phone,
    ) {}

    public static function fromModel(OrderAddress $address): self
    {
        return new self(
            name: $address->full_name,
            company: $address->company,
            address_1: $address->address_1,
            address_2: $address->address_2,
            city: $address->city,
            postcode: $address->postcode,
            country: $address->country,
            phone: $address->phone,
        );
    }
}
