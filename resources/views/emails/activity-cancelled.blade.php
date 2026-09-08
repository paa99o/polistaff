<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Aktiviti dibatalkan: {{ $activity->title }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <p style="margin-top: 0; color: #6b7280;">Aktiviti Polistaff</p>

        <h1 style="font-size: 22px; margin: 0 0 16px; color: #111827;">Aktiviti Dibatalkan</h1>

        <p>Aktiviti berikut telah dibatalkan:</p>

        <h2 style="font-size: 18px; margin: 16px 0; color: #111827;">{{ $activity->title }}</h2>

        <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; width: 120px;">Tarikh</td>
                <td style="padding: 8px 0;">{{ $activity->date_time->format('d/m/Y h:i A') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Lokasi</td>
                <td style="padding: 8px 0;">{{ $activity->location }}</td>
            </tr>
        </table>

        <p>Sila semak Polistaff untuk maklumat lanjut.</p>

        <p>
            <a href="{{ route('activities.show', $activity) }}" style="display: inline-block; background: #dc2626; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px;">
                Lihat Aktiviti
            </a>
        </p>

        <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">
            Emel ini dihantar secara automatik apabila aktiviti dibatalkan di Polistaff.
        </p>
    </div>
</body>
</html>
