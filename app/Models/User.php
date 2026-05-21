<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active', 'deactivated_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<UserInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class);
    }

    /**
     * Latest pending invitation, used by the admin UI to surface whether
     * the user has accepted yet.
     *
     * @return HasMany<UserInvitation, $this>
     */
    public function pendingInvitation(): HasMany
    {
        return $this->invitations()->whereNull('accepted_at')->latest();
    }
}
