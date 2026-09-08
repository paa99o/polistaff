<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Resit bayaran {{ $payment->transaction?->receipt_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <p style="margin-top: 0; color: #6b7280;">Resit Polistaff</p>

        <h1 style="font-size: 22px; margin: 0 0 16px; color: #111827;">Bayaran Diluluskan</h1>

        <p>Bayaran anda telah disahkan dan resit transaksi telah dijana.</p>

        <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; width: 150px;">No. Resit</td>
                <td style="padding: 8px 0;"><strong>{{ $payment->transaction?->receipt_number }}</strong></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Nama</td>
                <td style="padding: 8px 0;">{{ $payment->user->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Jumlah</td>
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
            @if($payment->allocated_amount)
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Diperuntukkan</td>
                    <td style="padding: 8px 0;">RM {{ number_format((float) $payment->allocated_amount, 2) }}</td>
                </tr>
            @endif
        </table>

        <p>
            <a href="{{ route('payments.show', $payment) }}" style="display: inline-block; background: #dc2626; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px;">
                Lihat Resit
            </a>
        </p>

        <p style="font-size: 13px; color: #6b7280; margin-bottom: 0;">
            Emel ini dihantar secara automatik apabila bayaran diluluskan di Polistaff.
        </p>
    </div>
</body>
</html>
