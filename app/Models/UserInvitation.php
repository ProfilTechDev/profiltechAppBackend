<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pending or accepted invitation for a user to set their initial password.
 * Tokens are random 64-char strings; they expire and can be re-issued by
 * admins. Only one un-accepted invitation per user is active at a time.
 */
#[Fillable([
    'user_id',
    'invited_by_user_id',
    'token',
    'expires_at',
    'accepted_at',
    'last_sent_at',
])]
class UserInvitation extends Model
{
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }
}
