<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Permohonan keahlian tidak diluluskan</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <p style="margin-top: 0; color: #6b7280;">Keahlian Polistaff</p>

        <h1 style="font-size: 22px; margin: 0 0 16px; color: #111827;">Permohonan Keahlian Tidak Diluluskan</h1>

        <p>Salam {{ $user->name }},</p>

        <p>Permohonan keahlian kelab staff anda tidak dapat diluluskan buat masa ini.</p>

        <p style="margin-bottom: 8px;"><strong>Sebab:</strong></p>
        <p style="white-space: pre-line; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">{{ $reason }}</p>

        <p>Sila kemas kini maklumat anda dan hantar permohonan semula jika perlu.</p>

        <p>
            <a href="{{ route('membership.apply') }}" style="display: inline-block; background: #dc2626; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px;">
                Kemaskini Permohonan
            </a>
        </p>

        <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">
            Emel ini dihantar secara automatik apabila permohonan keahlian disemak di Polistaff.
        </p>
    </div>
</body>
</html>
