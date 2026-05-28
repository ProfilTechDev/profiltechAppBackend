<?php

namespace App\Models;

use App\Enums\TourStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A planned self-delivery tour. Groups orders that will be delivered
 * together on a single day. Departments only see the contained orders
 * once the tour is approved — that gate is enforced in TourService.
 */
class Tour extends Model
{
    protected $fillable = [
        'name',
        'tour_date',
        'status',
        'notes',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'tour_date' => 'immutable_date',
        'status' => TourStatus::class,
        'approved_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsToMany<Order, $this, TourOrder>
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'tour_orders')
            ->using(TourOrder::class)
            ->withPivot(['primary_sequence', 'insert_index', 'added_after_approval'])
            ->withTimestamps();
    }

    /**
     * Raw pivot rows, ordered by sequence. Useful when the caller wants
     * the position metadata without paying for the full Order rows.
     *
     * @return HasMany<TourOrder, $this>
     */
    public function tourOrders(): HasMany
    {
        return $this->hasMany(TourOrder::class)
            ->orderBy('primary_sequence')
            ->orderBy('insert_index');
    }
}
