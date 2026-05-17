<?php

namespace App\Data\WooCommerce;

use Spatie\LaravelData\Data;

/**
 * A billing or shipping address from a WooCommerce order payload.
 * `email`/`phone` are usually only populated on billing.
 */
class AddressData extends Data
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?string $company,
        public string $address_1,
        public ?string $address_2,
        public string $city,
        public ?string $state,
        public string $postcode,
        public string $country,
        public ?string $email = null,
        public ?string $phone = null,
    ) {}
}
