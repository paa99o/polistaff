<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Keahlian diluluskan</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <p style="margin-top: 0; color: #6b7280;">Keahlian Polistaff</p>

        <h1 style="font-size: 22px; margin: 0 0 16px; color: #111827;">Keahlian Anda Telah Diluluskan</h1>

        <p>Salam {{ $user->name }},</p>

        <p>Permohonan keahlian kelab staff anda telah diluluskan. Akaun anda kini aktif dan boleh menggunakan kemudahan ahli di Polistaff.</p>

        <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; width: 140px;">Status</td>
                <td style="padding: 8px 0;">{{ \App\Support\PolistaffLabels::status($user->membership_status) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Tarikh Sertai</td>
                <td style="padding: 8px 0;">{{ $user->joined_date?->format('d/m/Y') ?? '-' }}</td>
            </tr>
            @if((float) $user->fee_balance > 0)
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Baki Yuran</td>
                    <td style="padding: 8px 0;">RM {{ number_format((float) $user->fee_balance, 2) }}</td>
                </tr>
            @endif
        </table>

        <p>
            <a href="{{ route('dashboard') }}" style="display: inline-block; background: #dc2626; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px;">
                Buka Dashboard
            </a>
        </p>

        <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">
            Emel ini dihantar secara automatik apabila permohonan keahlian diluluskan di Polistaff.
        </p>
    </div>
</body>
</html>
