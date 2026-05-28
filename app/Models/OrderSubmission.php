<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vendor-bound submission of a custom order — both the draft state
 * (saved per step in the UI) and the final send. `sent_at` is null
 * while the submission is still being prepared.
 */
class OrderSubmission extends Model
{
    protected $fillable = [
        'order_id',
        'status',
        'provider_id',
        'subject',
        'message',
        'sent_at',
        'received_at',
    ];

    protected $casts = [
        'status' => SubmissionStatus::class,
        'sent_at' => 'immutable_datetime',
        'received_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<OrderSubmissionLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderSubmissionLine::class);
    }
}
