<?php

namespace App\Enums;

/**
 * Categorisation of an order's shipping method, derived from the
 * matched Table Rate `rate_id` (or `instance_id` / `method_id`
 * fallback). Drives the warehouse frontend's flow split: external
 * carriers, self-delivery, and customer pickup are different
 * fulfilment paths.
 */
enum ShippingCategory: string
{
    case Pickup = 'pickup';
    case SelfDelivery = 'self_delivery';
    case ExternalCarrier = 'external_carrier';
    case Unknown = 'unknown';

    /**
     * Resolve the category for a shipping line by looking up its
     * identifiers against `config/shipping.php`. First match wins:
     * rate_id → instance_id → method_id → Unknown.
     */
    public static function fromShipping(
        ?int $rateId,
        ?int $instanceId,
        ?string $methodId,
    ): self {
        $config = (array) config('shipping');

        return self::lookup($config['rate_ids'] ?? [], $rateId)
            ?? self::lookup($config['instance_ids'] ?? [], $instanceId)
            ?? self::lookup($config['method_ids'] ?? [], $methodId)
            ?? self::Unknown;
    }

    /**
     * @param  array<string, array<int, int|string>>  $haystack
     */
    private static function lookup(array $haystack, int|string|null $needle): ?self
    {
        if ($needle === null || $needle === '') {
            return null;
        }

        foreach ($haystack as $category => $values) {
            if (\in_array($needle, (array) $values, true)) {
                return self::from($category);
            }
        }

        return null;
    }
}
