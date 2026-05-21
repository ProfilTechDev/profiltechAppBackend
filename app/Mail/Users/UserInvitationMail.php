<?php

namespace App\Mail\Users;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email sent to a newly-invited user with a link to set their password
 * and finalise their account. The accept URL is built from
 * `app.url_frontend` so the link points at the Nuxt SPA, not the API.
 */
class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly UserInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address((string) $this->invitation->user->email, (string) $this->invitation->user->name)],
            subject: 'Du er inviteret til Profiltech-admin',
        );
    }

    public function content(): Content
    {
        $this->invitation->loadMissing(['user', 'invitedByUser']);

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $acceptUrl = $frontendUrl.'/invitation/'.$this->invitation->token;

        return new Content(
            view: 'emails.users.invitation-html',
            text: 'emails.users.invitation-text',
            with: [
                'invitee' => $this->invitation->user,
                'inviter' => $this->invitation->invitedByUser,
                'acceptUrl' => $acceptUrl,
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }
}
