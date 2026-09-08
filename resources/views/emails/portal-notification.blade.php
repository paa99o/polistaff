<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>{{ $notification->title }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <p style="margin-top: 0; color: #6b7280;">Notifikasi Polistaff</p>

        <h1 style="font-size: 22px; margin: 0 0 16px; color: #111827;">{{ $notification->title }}</h1>

        <p style="white-space: pre-line; margin-bottom: 24px;">{{ $notification->message }}</p>

        @if($notification->link)
            <p>
                <a href="{{ url($notification->link) }}" style="display: inline-block; background: #dc2626; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px;">
                    Buka di Polistaff
                </a>
            </p>
        @endif

        <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">
            Emel ini dihantar secara automatik apabila notifikasi diterbitkan di Polistaff.
        </p>
    </div>
</body>
</html>
