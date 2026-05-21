<?php

namespace App\Policies;

use App\Authorization\Permissions;
use App\Models\User;

/**
 * All user-management actions gate on the single `users.manage`
 * permission. Super-admins bypass via the Gate::before hook registered
 * in AppServiceProvider — they don't need the permission explicitly.
 *
 * The policy also blocks a few self-targeting actions (a user can't
 * deactivate or delete themselves) so the UI can show those buttons
 * disabled for the current user without a separate guard.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function view(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function update(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE) && $user->id !== $target->id;
    }

    public function deactivate(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE) && $user->id !== $target->id;
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }

    public function resendInvitation(User $user, User $target): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }
}
