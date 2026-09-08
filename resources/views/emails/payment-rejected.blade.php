<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Bayaran ditolak</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <p style="margin-top: 0; color: #6b7280;">Bayaran Polistaff</p>

        <h1 style="font-size: 22px; margin: 0 0 16px; color: #111827;">Bayaran Ditolak</h1>

        <p>Salam {{ $payment->user->name }},</p>

        <p>Bayaran anda tidak dapat diluluskan buat masa ini.</p>

        <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; width: 150px;">Jumlah</td>
                <td style="padding: 8px 0;">RM {{ number_format((float) $payment->amount, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Tarikh Bayaran</td>
                <td style="padding: 8px 0;">{{ $payment->payment_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Kaedah</td>
                <td style="padding: 8px 0;">{{ $payment->payment_method }}</td>
            </tr>
        </table>

        <p style="margin-bottom: 8px;"><strong>Sebab ditolak:</strong></p>
        <p style="white-space: pre-line; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">{{ $payment->review_notes }}</p>

        <p>Sila semak catatan semakan dan hantar bukti bayaran baru jika perlu.</p>

        <p>
            <a href="{{ route('payments.show', $payment) }}" style="display: inline-block; background: #dc2626; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px;">
                Semak Bayaran
            </a>
        </p>

        <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">
            Emel ini dihantar secara automatik apabila bayaran ditolak di Polistaff.
        </p>
    </div>
</body>
</html>
