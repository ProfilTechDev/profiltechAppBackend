<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<title>{{ $t['product'] }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#222;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f4f4">
<tr>
<td align="center" style="padding:24px 12px;">

    <table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="max-width:640px;border:1px solid #e1e1e1;">

        <tr>
        <td style="padding:24px 24px 8px 24px;font-size:14px;line-height:1.5;white-space:pre-wrap;">{{ $body }}</td>
        </tr>

        <tr>
        <td style="padding:16px 24px 24px 24px;">

            <table role="presentation" width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;">
                <thead>
                <tr bgcolor="#f0f0f0">
                    <th align="left" style="border:1px solid #d4d4d4;padding:8px;font-weight:bold;">{{ $t['product'] }}</th>
                    <th align="center" width="80" style="border:1px solid #d4d4d4;padding:8px;font-weight:bold;">{{ $t['quantity_header'] }}</th>
                    <th align="center" width="100" style="border:1px solid #d4d4d4;padding:8px;font-weight:bold;">{{ $t['thickness'] }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($lines as $line)
                    <tr valign="top">
                        <td style="border:1px solid #d4d4d4;padding:8px;">
                            <strong>{{ $line->orderLine?->snapshot?->name ?? $t['unknown_product'] }}</strong>
                            @php
                                $attributes = $line->orderLine?->snapshot?->visibleAttributes($lang) ?? collect();
                            @endphp
                            @if ($attributes->isNotEmpty())
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:6px;font-size:13px;color:#555;">
                                    @foreach ($attributes as $attr)
                                        <tr>
                                            <td style="padding:1px 8px 1px 0;white-space:nowrap;">{{ $attr->label }}:</td>
                                            <td style="padding:1px 0;">{{ $attr->value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif
                        </td>
                        <td align="center" style="border:1px solid #d4d4d4;padding:8px;">{{ $line->quantity }} {{ $t['quantity_unit'] }}</td>
                        <td align="center" style="border:1px solid #d4d4d4;padding:8px;">
                            @if ($line->thickness !== null)
                                {{ rtrim(rtrim((string) $line->thickness, '0'), '.') }} mm
                            @else
                                &mdash;
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </td>
        </tr>

    </table>

</td>
</tr>
</table>

</body>
</html>
