<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Tuntutan diluluskan</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <p style="margin-top: 0; color: #6b7280;">Tuntutan Polistaff</p>
        <h1 style="font-size: 22px; margin: 0 0 16px; color: #111827;">Tuntutan Diluluskan</h1>
        <p>Salam {{ $claim->user->name }},</p>
        <p>Tuntutan anda telah diluluskan dan transaksi perbelanjaan telah dijana.</p>
        <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px 0; color: #6b7280; width: 150px;">Tuntutan</td><td style="padding: 8px 0;">{{ $claim->title }}</td></tr>
            <tr><td style="padding: 8px 0; color: #6b7280;">Jumlah</td><td style="padding: 8px 0;">RM {{ number_format((float) $claim->amount, 2) }}</td></tr>
            <tr><td style="padding: 8px 0; color: #6b7280;">No. Transaksi</td><td style="padding: 8px 0;"><strong>{{ $claim->transaction?->receipt_number }}</strong></td></tr>
            <tr><td style="padding: 8px 0; color: #6b7280;">Tarikh Tuntutan</td><td style="padding: 8px 0;">{{ $claim->claim_date->format('d/m/Y') }}</td></tr>
        </table>
        @if($claim->review_notes)
            <p style="margin-bottom: 8px;"><strong>Catatan kelulusan:</strong></p>
            <p style="white-space: pre-line; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">{{ $claim->review_notes }}</p>
        @endif
        <p><a href="{{ route('claims.show', $claim) }}" style="display: inline-block; background: #dc2626; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px;">Lihat Tuntutan</a></p>
        <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">Emel ini dihantar secara automatik apabila tuntutan diluluskan di Polistaff.</p>
    </div>
</body>
</html>
