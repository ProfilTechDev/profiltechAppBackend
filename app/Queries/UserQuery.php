<?php

namespace App\Queries;

use App\Authorization\Permissions;
use App\Data\Users\InvitationPreviewData;
use App\Http\Filters\Users\UserPermissionFilter;
use App\Http\Filters\Users\UserSearchFilter;
use App\Http\Filters\Users\UserStatusFilter;
use App\Models\User;
use App\Models\UserInvitation;
use App\Queries\Concerns\AppliesSort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-side for the Users domain — every list/lookup the controller
 * exposes to the frontend. Mirrors `UserService` (commands) so the
 * controller depends on one of each per request.
 */
class UserQuery
{
    use AppliesSort;

    private const ALLOWED_SORT_FIELDS = ['name', 'email', 'created_at'];

    /**
     * Paginated user list with optional search/status/permission
     * filters. Sort defaults to ascending name; unknown sort fields
     * fall back to the default silently.
     */
    public function list(
        ?string $search = null,
        ?string $status = null,
        ?string $permission = null,
        string $sort = 'name',
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = User::query();

        (new UserSearchFilter)($query, $search);
        (new UserStatusFilter)($query, $status);
        (new UserPermissionFilter)($query, $permission);

        $this->applySort($query, $sort, self::ALLOWED_SORT_FIELDS, 'name');

        return $query
            ->with(['permissions', 'pendingInvitation'])
            ->paginate($perPage);
    }

    /**
     * Static permission catalogue the frontend renders as checkboxes.
     * Backed by `App\Authorization\Permissions`.
     *
     * @return array<int, array{key: string, label: string}>
     */
    public function permissions(): array
    {
        return collect(Permissions::all())
            ->map(fn (string $label, string $key): array => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * Preview shown on the public invitation-accept page. Returns null
     * if the invitation is expired or already used so the controller
     * can map it to a 410.
     */
    public function invitationPreview(UserInvitation $invitation): ?InvitationPreviewData
    {
        if (! $invitation->isUsable()) {
            return null;
        }

        return InvitationPreviewData::from($invitation);
    }
}
