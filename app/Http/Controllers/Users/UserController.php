<?php

namespace App\Http\Controllers\Users;

use App\Authorization\Permissions;
use App\Data\Users\UserData;
use App\Http\Filters\Users\UserPermissionFilter;
use App\Http\Filters\Users\UserSearchFilter;
use App\Http\Filters\Users\UserStatusFilter;
use App\Http\Requests\Users\ResetUserPasswordRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use App\Services\Users\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Admin endpoints for managing the internal user list. Gated behind
 * the `users.manage` permission via UserPolicy.
 */
class UserController
{
    public function __construct(
        private readonly UserService $service,
    ) {}

    /**
     * Static metadata the frontend needs to render the permission
     * checkboxes — keeps key strings in sync with backend constants.
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

    public function index(Request $request): LengthAwarePaginator
    {
        $this->authorizeFor($request, 'viewAny', User::class);

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $users = QueryBuilder::for(User::query())
            ->allowedFilters(
                AllowedFilter::custom('search', new UserSearchFilter),
                AllowedFilter::custom('status', new UserStatusFilter),
                AllowedFilter::custom('permission', new UserPermissionFilter),
            )
            ->allowedSorts('name', 'email', 'created_at')
            ->defaultSort('name')
            ->with(['permissions', 'pendingInvitation'])
            ->paginate($perPage)
            ->appends($request->query());

        return UserData::collect($users);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeFor($request, 'view', $user);

        return response()->json(UserData::from($user));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorizeFor($request, 'create', User::class);

        $user = $this->service->createWithInvitation(
            name: (string) $request->validated('name'),
            email: (string) $request->validated('email'),
            permissions: $request->permissions(),
            invitedBy: $request->user(),
        );

        return response()->json(UserData::from($user), 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorizeFor($request, 'update', $user);

        $user = $this->service->update(
            user: $user,
            name: (string) $request->validated('name', $user->name),
            email: (string) $request->validated('email', $user->email),
            permissions: $request->permissionsOrNull(),
        );

        return response()->json(UserData::from($user));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeFor($request, 'delete', $user);

        $user->delete();

        return response()->json(['deleted' => true]);
    }

    public function deactivate(Request $request, User $user): JsonResponse
    {
        $this->authorizeFor($request, 'deactivate', $user);

        $this->service->deactivate($user);

        return response()->json(UserData::from($user->fresh()));
    }

    public function reactivate(Request $request, User $user): JsonResponse
    {
        $this->authorizeFor($request, 'update', $user);

        $this->service->reactivate($user);

        return response()->json(UserData::from($user->fresh()));
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): JsonResponse
    {
        $this->authorizeFor($request, 'resetPassword', $user);

        $this->service->resetPassword($user, (string) $request->validated('password'));

        return response()->json(['reset' => true]);
    }

    public function resendInvitation(Request $request, User $user): JsonResponse
    {
        $this->authorizeFor($request, 'resendInvitation', $user);

        $this->service->resendInvitation($user, $request->user());

        return response()->json(['sent' => true]);
    }

    /**
     * Thin wrapper around the Gate so each handler stays one-liner with
     * a 403 on policy failure.
     *
     * @param  User|class-string  $target
     */
    private function authorizeFor(Request $request, string $ability, mixed $target): void
    {
        if (! $request->user()?->can($ability, $target)) {
            abort(403);
        }
    }
}
