<?php

namespace App\Http\Filters\Fulfillment;

use App\Enums\DeliveryFlow;
use App\Http\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * `?filter[delivery_flow]=delivery|shipping|pickup|none[,…]` — exact
 * match on `orders.delivery_flow`. The pseudo-value `none` matches
 * orders that haven't been assigned a flow yet (NULL) — that's the
 * "Til planlægning" bucket.
 */
class DeliveryFlowFilter extends Filter
{
    protected function apply(Builder $query, string $value): void
    {
        $allowedFlows = array_column(DeliveryFlow::cases(), 'value');

        $requested = array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $v): bool => $v === 'none' || in_array($v, $allowedFlows, true),
        );

        if ($requested === []) {
            return;
        }

        $query->where(function (Builder $q) use ($requested): void {
            $flows = array_values(array_diff($requested, ['none']));

            if ($flows !== []) {
                $q->whereIn('delivery_flow', $flows);
            }

            if (in_array('none', $requested, true)) {
                $q->orWhereNull('delivery_flow');
            }
        });
    }
}
