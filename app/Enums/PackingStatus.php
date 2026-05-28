<?php

namespace App\Enums;

/**
 * Where an order is in the warehouse pipeline. The valid values depend
 * on the order's DeliveryFlow — Delivery has the full sequence
 * (planning → packing → packed → out → done), Shipping is the trivial
 * pair (packing → done), Pickup has the customer-awaiting step
 * (packing → awaiting → done).
 *
 * `null` packing_status means the order isn't in the pipeline yet —
 * typically because delivery_flow is also null (admin hasn't decided
 * how it fulfils).
 */
enum PackingStatus: string
{
    case NotPlanned = 'not_planned';
    case Packing = 'packing';
    case Packed = 'packed';
    case OutForDelivery = 'out_for_delivery';
    case AwaitingPickup = 'awaiting_pickup';
    case Done = 'done';

    /**
     * Statuses the given flow is allowed to use, in the order they
     * usually progress through. Used both to validate input and to
     * drive the per-flow status dropdown / actions menu on the
     * frontend.
     *
     * `$onTour` only matters for Delivery: an unscheduled delivery
     * order can only stay NotPlanned or jump straight to Done (e.g.
     * cancelled / handled outside the route flow). The intermediate
     * packing-pipeline states are gated on being placed on a route.
     *
     * @return array<int, self>
     */
    public static function allowedFor(?DeliveryFlow $flow, bool $onTour = true): array
    {
        return match ($flow) {
            DeliveryFlow::Delivery => $onTour
                ? [self::NotPlanned, self::Packing, self::Packed, self::OutForDelivery, self::Done]
                : [self::NotPlanned, self::Done],
            DeliveryFlow::Shipping => [
                self::Packing,
                self::Done,
            ],
            DeliveryFlow::Pickup => [
                self::Packing,
                self::AwaitingPickup,
                self::Done,
            ],
            null => [],
        };
    }

    /**
     * Initial status when an order first lands in a flow. Delivery
     * starts out unplanned (waiting for a tour assignment); Shipping
     * and Pickup go straight into packing since they don't need a
     * tour gate.
     */
    public static function defaultFor(?DeliveryFlow $flow): ?self
    {
        return match ($flow) {
            DeliveryFlow::Delivery => self::NotPlanned,
            DeliveryFlow::Shipping, DeliveryFlow::Pickup => self::Packing,
            null => null,
        };
    }

    public function isAllowedFor(?DeliveryFlow $flow, bool $onTour = true): bool
    {
        return in_array($this, self::allowedFor($flow, $onTour), true);
    }
}
