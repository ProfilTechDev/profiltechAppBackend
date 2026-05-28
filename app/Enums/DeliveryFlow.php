<?php

namespace App\Enums;

/**
 * Admin-controlled fulfilment flow for an order.
 *
 * Initially derived from the order's OrderShipment.category at sync time
 * but admins can override it in the planning UI (e.g. flip a Delivery
 * order to Pickup because the customer decided to collect it, or move a
 * Shipping order onto a Delivery tour because the truck passes nearby).
 *
 *   - Delivery: self-delivered on a planned tour (Levering)
 *   - Shipping: handed to an external carrier (Sende)
 *   - Pickup:   customer collects at the warehouse (Afhentning)
 *
 * `null` means an order hasn't been assigned a flow yet — admins see
 * those in the "Til planlægning" bucket.
 */
enum DeliveryFlow: string
{
    case Delivery = 'delivery';
    case Shipping = 'shipping';
    case Pickup = 'pickup';

    /**
     * Initial flow derived from the order's shipping category. Returns
     * null for Unknown so the order surfaces for manual triage rather
     * than landing in the wrong bucket silently.
     */
    public static function fromShippingCategory(?ShippingCategory $category): ?self
    {
        return match ($category) {
            ShippingCategory::Pickup => self::Pickup,
            ShippingCategory::SelfDelivery => self::Delivery,
            ShippingCategory::ExternalCarrier => self::Shipping,
            default => null,
        };
    }
}
