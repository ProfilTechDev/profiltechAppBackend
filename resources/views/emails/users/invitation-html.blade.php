<!DOCTYPE html>
<html lang="da">
<head>
<meta charset="utf-8">
<title>Invitation til Profiltech</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#222;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f4f4">
<tr>
<td align="center" style="padding:24px 12px;">

    <table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="max-width:640px;border:1px solid #e1e1e1;">

        <tr>
        <td style="padding:24px 24px 8px 24px;font-size:18px;font-weight:bold;">Hej {{ $invitee->name }}</td>
        </tr>

        <tr>
        <td style="padding:0 24px 16px 24px;font-size:14px;line-height:1.5;">
            {{ $inviter ? $inviter->name.' har inviteret dig' : 'Du er inviteret' }} til Profiltech-administrationspanelet.
            Klik på knappen herunder for at sætte et password og logge ind for første gang.
        </td>
        </tr>

        <tr>
        <td align="center" style="padding:8px 24px 24px 24px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                <td bgcolor="#1f2937" style="border-radius:4px;">
                    <a href="{{ $acceptUrl }}" style="display:inline-block;padding:12px 24px;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">Aktivér min konto</a>
                </td>
                </tr>
            </table>
        </td>
        </tr>

        <tr>
        <td style="padding:0 24px 24px 24px;font-size:13px;color:#555;line-height:1.5;">
            Hvis knappen ikke virker, kopiér dette link ind i din browser:<br>
            <a href="{{ $acceptUrl }}" style="color:#1f2937;word-break:break-all;">{{ $acceptUrl }}</a>
        </td>
        </tr>

        <tr>
        <td style="padding:0 24px 24px 24px;font-size:13px;color:#555;line-height:1.5;">
            Linket udløber {{ $expiresAt->isoFormat('D. MMMM YYYY [kl.] HH:mm') }}.
            Hvis du ikke har bedt om denne invitation, kan du ignorere mailen.
        </td>
        </tr>

    </table>

</td>
</tr>
</table>

</body>
</html>
