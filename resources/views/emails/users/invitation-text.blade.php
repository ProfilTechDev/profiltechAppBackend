Hej {{ $invitee->name }}

{{ $inviter ? $inviter->name.' har inviteret dig' : 'Du er inviteret' }} til Profiltech-administrationspanelet.

Klik på linket herunder for at sætte et password og logge ind for første gang:

{{ $acceptUrl }}

Linket udløber {{ $expiresAt->isoFormat('D. MMMM YYYY [kl.] HH:mm') }}.

Hvis du ikke har bedt om denne invitation, kan du ignorere mailen.
