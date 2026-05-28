<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot row connecting an Order to a Tour at a specific sequence slot.
 *
 * Sequence is encoded as two columns:
 *   - primary_sequence: 1, 2, 3 …  the slot the packers write on the box.
 *   - insert_index:     0 = original; 1 = A, 2 = B, 3 = C, …
 *                       Non-zero rows were inserted after the tour was
 *                       approved (so the printed paper labels stay
 *                       valid; we only add new ones).
 *
 * The display_sequence accessor renders e.g. "2A" — that's the string
 * the warehouse paper shows.
 */
class TourOrder extends Pivot
{
    public $incrementing = true;

    protected $table = 'tour_orders';

    protected $fillable = [
        'tour_id',
        'order_id',
        'primary_sequence',
        'insert_index',
        'added_after_approval',
    ];

    protected $casts = [
        'primary_sequence' => 'integer',
        'insert_index' => 'integer',
        'added_after_approval' => 'boolean',
    ];

    /**
     * @return BelongsTo<Tour, $this>
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * "2A", "3", "12B" — what the paper packing slip shows.
     */
    protected function displaySequence(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->insert_index === 0
                ? (string) $this->primary_sequence
                : $this->primary_sequence.chr(64 + $this->insert_index),
        );
    }

    /**
     * Stable sort key — primary * 100 leaves room for up to 99 inserts
     * per slot, which is more than the floor will ever produce.
     */
    protected function sortKey(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->primary_sequence * 100 + $this->insert_index,
        );
    }
}
