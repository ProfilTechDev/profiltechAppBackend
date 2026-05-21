<?php

namespace App\Data\Users;

use App\Models\User;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * User payload returned by the admin user management endpoints.
 * Includes the user's currently-assigned permissions as a flat array
 * of keys, and a `has_pending_invitation` flag for the list view.
 */
class UserData extends Data
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $is_active,
        public ?CarbonImmutable $deactivated_at,
        public ?CarbonImmutable $email_verified_at,
        public bool $has_pending_invitation,
        /** @var array<int, string> */
        public array $permissions,
        public CarbonImmutable $created_at,
    ) {}

    public static function fromModel(User $user): self
    {
        $user->loadMissing(['permissions', 'pendingInvitation']);

        return new self(
            id: $user->id,
            name: (string) $user->name,
            email: (string) $user->email,
            is_active: (bool) $user->is_active,
            deactivated_at: $user->deactivated_at?->toImmutable(),
            email_verified_at: $user->email_verified_at?->toImmutable(),
            has_pending_invitation: $user->pendingInvitation->isNotEmpty(),
            permissions: $user->permissions->pluck('name')->all(),
            created_at: $user->created_at->toImmutable(),
        );
    }
}
