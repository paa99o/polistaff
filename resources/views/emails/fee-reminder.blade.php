<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Peringatan yuran tertunggak</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <p style="margin-top: 0; color: #6b7280;">Yuran Polistaff</p>

        <h1 style="font-size: 22px; margin: 0 0 16px; color: #111827;">Peringatan Yuran Tertunggak</h1>

        <p>Salam {{ $user->name }},</p>

        <p>Anda mempunyai baki yuran tertunggak sebanyak:</p>

        <p style="font-size: 28px; font-weight: bold; margin: 16px 0; color: #111827;">
            RM {{ number_format((float) $user->fee_balance, 2) }}
        </p>

        <p>Sila buat bayaran melalui Polistaff untuk mengemas kini status yuran anda.</p>

        <p>
            <a href="{{ route('payments.create') }}" style="display: inline-block; background: #dc2626; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px;">
                Bayar Yuran
            </a>
        </p>

        <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">
            Emel ini dihantar secara automatik kepada ahli aktif yang mempunyai baki yuran tertunggak.
        </p>
    </div>
</body>
</html>
