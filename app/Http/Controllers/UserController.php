<?php

namespace App\Http\Controllers;

use App\Data\Users\UserData;
use App\Http\Requests\Users\AcceptInvitationRequest;
use App\Http\Requests\Users\DeactivateUserRequest;
use App\Http\Requests\Users\DestroyUserRequest;
use App\Http\Requests\Users\ListUsersRequest;
use App\Http\Requests\Users\ReactivateUserRequest;
use App\Http\Requests\Users\ResendInvitationRequest;
use App\Http\Requests\Users\ResetUserPasswordRequest;
use App\Http\Requests\Users\ShowUserRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use App\Models\UserInvitation;
use App\Queries\UserQuery;
use App\Services\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints for the Users domain — both the admin-facing CRUD (auth
 * required, gated by UserPolicy) and the public invitation flow
 * (no auth, token-bound). Kept in a single controller because the
 * shared service/query layer means splitting added indirection
 * without separation of concerns.
 *
 * Every action gets its own FormRequest — even the no-body ones —
 * so policy checks live in the standard Laravel place rather than
 * inline in the controller. Reads delegate to `UserQuery`; commands
 * to `UserService`.
 */
class UserController
{
    public function __construct(
        private readonly UserService $service,
        private readonly UserQuery $query,
    ) {}

    public function index(ListUsersRequest $request): LengthAwarePaginator
    {
        $users = $this->query->list(
            search: $request->input('filter.search'),
            status: $request->input('filter.status'),
            permission: $request->input('filter.permission'),
            sort: (string) $request->input('sort', 'name'),
            perPage: (int) $request->input('per_page', 20),
        );

        return UserData::collect($users->appends($request->query()));
    }

    public function show(ShowUserRequest $request, User $user): JsonResponse
    {
        return response()->json(UserData::from($user));
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function permissions(): array
    {
        return $this->query->permissions();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
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
        $user = $this->service->update(
            user: $user,
            name: (string) $request->validated('name', $user->name),
            email: (string) $request->validated('email', $user->email),
            permissions: $request->permissionsOrNull(),
        );

        return response()->json(UserData::from($user));
    }

    public function destroy(DestroyUserRequest $request, User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['deleted' => true]);
    }

    public function deactivate(DeactivateUserRequest $request, User $user): JsonResponse
    {
        $this->service->deactivate($user);

        return response()->json(UserData::from($user->fresh()));
    }

    public function reactivate(ReactivateUserRequest $request, User $user): JsonResponse
    {
        $this->service->reactivate($user);

        return response()->json(UserData::from($user->fresh()));
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): JsonResponse
    {
        $this->service->resetPassword($user, (string) $request->validated('password'));

        return response()->json(['reset' => true]);
    }

    public function resendInvitation(ResendInvitationRequest $request, User $user): JsonResponse
    {
        $this->service->resendInvitation($user, $request->user());

        return response()->json(['sent' => true]);
    }

    /**
     * GET /invitations/{token} — preview shown on the invitation-accept
     * page. No auth: the invitee has no session yet.
     */
    public function showInvitation(UserInvitation $invitation): JsonResponse
    {
        $preview = $this->query->invitationPreview($invitation);

        if ($preview === null) {
            return response()->json(
                ['message' => 'Invitationen er udløbet eller allerede brugt.'],
                410,
            );
        }

        return response()->json($preview);
    }

    /**
     * POST /invitations/{token} — sets password and activates the user.
     */
    public function acceptInvitation(AcceptInvitationRequest $request, UserInvitation $invitation): JsonResponse
    {
        $this->service->acceptInvitation(
            invitation: $invitation,
            password: (string) $request->validated('password'),
        );

        return response()->json(['accepted' => true]);
    }
}
