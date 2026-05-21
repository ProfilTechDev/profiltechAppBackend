<?php

namespace App\Http\Controllers\Users;

use App\Data\Users\InvitationPreviewData;
use App\Http\Requests\Users\AcceptInvitationRequest;
use App\Models\UserInvitation;
use App\Services\Users\UserService;
use Illuminate\Http\JsonResponse;

/**
 * Public endpoints for the invitation-accept flow. Token is the route
 * binding key; if the token doesn't exist Laravel returns 404 before
 * the controller runs. Expired/used tokens get a 422 from the service.
 */
class UserInvitationController
{
    public function __construct(
        private readonly UserService $service,
    ) {}

    /**
     * GET /invitations/{token} — returns a minimal preview the Nuxt
     * accept page can render ("Hej Mattias, sæt dit password").
     */
    public function show(UserInvitation $invitation): JsonResponse
    {
        if (! $invitation->isUsable()) {
            return response()->json(
                ['message' => 'Invitationen er udløbet eller allerede brugt.'],
                410,
            );
        }

        return response()->json(InvitationPreviewData::from($invitation));
    }

    /**
     * POST /invitations/{token} — accepts the invitation, sets the
     * password, activates the user. Frontend can then redirect to
     * /login.
     */
    public function accept(AcceptInvitationRequest $request, UserInvitation $invitation): JsonResponse
    {
        $this->service->acceptInvitation(
            invitation: $invitation,
            password: (string) $request->validated('password'),
        );

        return response()->json(['accepted' => true]);
    }
}
