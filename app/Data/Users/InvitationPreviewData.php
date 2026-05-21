<?php

namespace App\Data\Users;

use App\Models\UserInvitation;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * Public shape returned by the "show invitation" endpoint — what the
 * Nuxt accept-invitation page needs to render before the user submits
 * a password. Contains only safe-to-leak fields.
 */
class InvitationPreviewData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $invited_by_name,
        public CarbonImmutable $expires_at,
    ) {}

    public static function fromModel(UserInvitation $invitation): self
    {
        $invitation->loadMissing(['user', 'invitedByUser']);

        return new self(
            name: (string) $invitation->user->name,
            email: (string) $invitation->user->email,
            invited_by_name: $invitation->invitedByUser?->name,
            expires_at: $invitation->expires_at->toImmutable(),
        );
    }
}
