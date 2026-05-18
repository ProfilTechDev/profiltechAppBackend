<?php

namespace App\Data\CustomOrders;

use Spatie\LaravelData\Data;

/**
 * Provider info for the frontend dropdown. Emails are exposed since the
 * frontend is an authenticated admin tool — vendors and their addresses
 * are operational data for the people using it.
 */
class ProviderData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $language,
    ) {}
}
