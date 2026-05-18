<?php

namespace App\Models;

use App\Data\Orders\AttributeData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A snapshot of a Product as it existed at the time an OrderLine was created.
 *
 * Fields here are intentionally denormalised from the products table and
 * are never overwritten by later product updates — they represent what
 * the customer actually purchased. Attributes (colour, dimensions etc.)
 * live in the related `order_line_attributes` table — accessed via
 * `lineAttributes()` (the relation can't be called `attributes` because
 * that collides with Eloquent's internal $attributes array).
 */
class OrderLineProduct extends Model
{
    protected $fillable = [
        'order_line_id',
        'name',
        'is_custom',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    /**
     * @return BelongsTo<OrderLine, $this>
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }

    /**
     * @return HasMany<OrderLineAttribute, $this>
     */
    public function lineAttributes(): HasMany
    {
        return $this->hasMany(OrderLineAttribute::class)->orderBy('position');
    }

    /**
     * Attributes filtered + transformed via config/custom_orders.php
     * `attribute_overrides`. Only keys present in the override map are
     * surfaced — anything else is treated as internal and hidden.
     *
     * Each override may rewrite the label and remap display values
     * (e.g. "Med dripstopdug + 29 kr. pr. m²" → "Ja").
     *
     * Color lookup is NOT done here — frontend joins against the
     * `/products/colors` endpoint via `raw_value`.
     *
     * @return Collection<int, AttributeData>
     */
    public function visibleAttributes(): Collection
    {
        $overrides = (array) config('custom_orders.attribute_overrides', []);

        return $this->lineAttributes
            ->filter(fn (OrderLineAttribute $attr): bool => isset($overrides[$attr->key]))
            ->map(function (OrderLineAttribute $attr) use ($overrides): AttributeData {
                $override = $overrides[$attr->key];

                return new AttributeData(
                    key: $attr->key,
                    label: (string) ($override['label'] ?? $attr->label),
                    value: $this->mapValue($attr->value, (array) ($override['value_map'] ?? [])),
                    raw_value: $attr->raw_value,
                );
            })
            ->values();
    }

    /**
     * Resolve a display value against a map: exact match wins, otherwise
     * case-insensitive starts-with on each map key. Falls through to the
     * original value if nothing matches.
     *
     * @param  array<string, string>  $map
     */
    private function mapValue(string $value, array $map): string
    {
        if ($map === []) {
            return $value;
        }

        if (isset($map[$value])) {
            return $map[$value];
        }

        foreach ($map as $needle => $replacement) {
            if (stripos($value, (string) $needle) === 0) {
                return (string) $replacement;
            }
        }

        return $value;
    }
}
