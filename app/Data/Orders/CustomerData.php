<?php

namespace App\Data\Orders;

use App\Models\OrderCustomer;
use Spatie\LaravelData\Data;

/**
 * Customer snapshot attached to an order. `name` is the concatenated
 * full name from the underlying OrderCustomer model.
 */
class CustomerData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
    ) {}

    public static function fromModel(OrderCustomer $customer): self
    {
        return new self(
            name: $customer->full_name,
            email: $customer->email,
            phone: $customer->phone,
        );
    }
}
