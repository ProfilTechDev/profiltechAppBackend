<?php

namespace App\Services;

use App\Authorization\Permissions;
use App\Mail\Users\UserInvitationMail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Domain logic for user management — the only layer allowed to mutate
 * user state (creation, permission sync, deactivation, invitation
 * lifecycle, admin-initiated password reset). Controllers delegate here;
 * the queued mail job for invitations also calls through here for
 * consistency.
 */
class UserService
{
    private const INVITATION_TTL_DAYS = 7;

    /**
     * Create a deactivated user and a fresh invitation, then queue the
     * invitation email. The user can sign in only after accepting the
     * invitation (which sets the password and flips `is_active`).
     *
     * @param  array<int, string>  $permissions  Permission names from App\Authorization\Permissions.
     */
    public function createWithInvitation(
        string $name,
        string $email,
        array $permissions,
        ?User $invitedBy = null,
    ): User {
        return DB::transaction(function () use ($name, $email, $permissions, $invitedBy): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'is_active' => false,
            ]);

            $user->syncPermissions($this->filterPermissions($permissions));

            $invitation = $this->issueInvitation($user, $invitedBy);
            $this->sendInvitationMail($invitation);

            return $user;
        });
    }

    /**
     * Update an existing user's profile and permissions. Email changes
     * keep the same user — they don't trigger a new invitation. Pass
     * `$permissions = null` to leave permissions untouched.
     *
     * @param  array<int, string>|null  $permissions
     */
    public function update(
        User $user,
        string $name,
        string $email,
        ?array $permissions = null,
    ): User {
        return DB::transaction(function () use ($user, $name, $email, $permissions): User {
            $user->update(['name' => $name, 'email' => $email]);

            if ($permissions !== null) {
                $user->syncPermissions($this->filterPermissions($permissions));
            }

            return $user->fresh()?->load('permissions') ?? $user;
        });
    }

    /**
     * Soft-style deactivation: flips `is_active=false` and stamps
     * `deactivated_at`. The user row is kept for history (orders,
     * audit). Reactivation is a separate explicit call.
     */
    public function deactivate(User $user): void
    {
        $user->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);
    }

    public function reactivate(User $user): void
    {
        $user->update([
            'is_active' => true,
            'deactivated_at' => null,
        ]);
    }

    /**
     * Admin-initiated password reset. Sets the new password directly —
     * does NOT go through Fortify's email-based reset flow (that's for
     * end-user "I forgot my password"). Returns nothing; callers should
     * surface success in the response themselves.
     */
    public function resetPassword(User $user, string $newPassword): void
    {
        $user->update(['password' => Hash::make($newPassword)]);
    }

    /**
     * Issue a fresh invitation for a user (e.g. resend after expiry).
     * Replaces any existing un-accepted invitation token so old links
     * stop working.
     */
    public function resendInvitation(User $user, ?User $invitedBy = null): UserInvitation
    {
        return DB::transaction(function () use ($user, $invitedBy): UserInvitation {
            $user->invitations()->whereNull('accepted_at')->delete();

            $invitation = $this->issueInvitation($user, $invitedBy);
            $this->sendInvitationMail($invitation);

            return $invitation;
        });
    }

    /**
     * Complete an invitation: set the password, activate the user, and
     * stamp the invitation accepted. The validation exception is thrown
     * if the token is already accepted or expired so the controller can
     * map it to a 422.
     */
    public function acceptInvitation(UserInvitation $invitation, string $password): User
    {
        if (! $invitation->isUsable()) {
            throw ValidationException::withMessages([
                'token' => 'Invitationen er udløbet eller allerede brugt.',
            ]);
        }

        return DB::transaction(function () use ($invitation, $password): User {
            $user = $invitation->user;

            $user->update([
                'password' => Hash::make($password),
                'is_active' => true,
                'deactivated_at' => null,
                'email_verified_at' => now(),
            ]);

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });
    }

    private function issueInvitation(User $user, ?User $invitedBy): UserInvitation
    {
        return UserInvitation::create([
            'user_id' => $user->id,
            'invited_by_user_id' => $invitedBy?->id,
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addDays(self::INVITATION_TTL_DAYS),
            'last_sent_at' => now(),
        ]);
    }

    private function sendInvitationMail(UserInvitation $invitation): void
    {
        Mail::send(new UserInvitationMail($invitation));
    }

    /**
     * Drop any permission key that isn't in the canonical list. Defence-
     * in-depth against the FormRequest being bypassed or rotting out of
     * sync with the constants.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function filterPermissions(array $permissions): array
    {
        return array_values(array_intersect($permissions, Permissions::keys()));
    }
}
