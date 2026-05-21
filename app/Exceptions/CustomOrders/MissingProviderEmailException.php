<?php

namespace App\Exceptions\CustomOrders;

use RuntimeException;

/**
 * Thrown when an OrderSubmission references a provider_id that has no
 * email configured. This is a configuration issue (fix
 * config/custom_orders.php and `.env`), not a transient error — the
 * queue still retries, but no number of retries will resolve it. Use
 * the `providerId` property in listeners that want to point operators
 * at the broken config entry.
 */
class MissingProviderEmailException extends RuntimeException
{
    public function __construct(public readonly string $providerId)
    {
        parent::__construct(
            "Missing email for provider '{$providerId}'. Check config/custom_orders.php and .env.",
        );
    }
}
