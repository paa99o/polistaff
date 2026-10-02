@component('mail::message')
# @if($order->status === 'pending') Pesanan diterima @else Status pesanan dikemas kini @endif

Hai {{ $order->customer_name }},

Pesanan **{{ $order->order_number }}** kini berstatus **{{ ucfirst($order->status) }}**.

Status bayaran: **{{ ['awaiting_payment' => 'Menunggu bayaran', 'proof_submitted' => 'Bukti dihantar untuk semakan', 'paid' => 'Bayaran disahkan', 'rejected' => 'Bukti perlu dihantar semula', 'expired' => 'Pesanan luput', 'cancelled' => 'Pesanan dibatalkan', 'refund_required' => 'Bayaran perlu dipulangkan secara manual', 'refunded' => 'Bayaran ditandakan telah dipulangkan'][$order->payment_status] ?? $order->payment_status }}**
@if($order->payment_review_note)

Catatan penjual: {{ $order->payment_review_note }}
@endif

@foreach($order->items as $item)
- {{ $item['name'] }} × {{ $item['quantity'] }} — RM {{ number_format((float) $item['price'] * (int) $item['quantity'], 2) }}
@endforeach

Jumlah pesanan: **RM {{ number_format((float) $order->total, 2) }}**

@if(in_array($order->payment_status, ['awaiting_payment', 'rejected'], true) && ($order->payment_instructions['method'] ?? null) === 'qr')
Kaedah bayaran: **QR**<br>
Nama penerima: {{ $order->payment_instructions['account_name'] ?? 'Penjual PoliMart' }}<br>
[Buka QR bayaran]({{ asset('storage/'.$order->payment_instructions['qr_code_path']) }})
@elseif(in_array($order->payment_status, ['awaiting_payment', 'rejected'], true) && ($order->payment_instructions['method'] ?? null) === 'bank_transfer')
Kaedah bayaran: **Pindahan bank**<br>
Bank: {{ $order->payment_instructions['bank_name'] }}<br>
Nama akaun: {{ $order->payment_instructions['account_name'] }}<br>
Nombor akaun: **{{ $order->payment_instructions['account_number'] }}**
@elseif(in_array($order->payment_status, ['awaiting_payment', 'rejected'], true) && ($order->payment_instructions['method'] ?? null) === 'fpx')
Kaedah bayaran: **FPX simulasi melalui {{ $order->payment_instructions['fpx_bank'] ?? 'bank pilihan' }}**<br>
Halaman bank dalam PoliMart ialah simulasi dan bukan gateway. Buat pindahan sebenar ke akaun penjual melalui aplikasi bank, kemudian hantar bukti melalui pautan semakan.<br>
Bank penerima: {{ $order->payment_instructions['bank_name'] }}<br>
Nama akaun: {{ $order->payment_instructions['account_name'] }}<br>
Nombor akaun: **{{ $order->payment_instructions['account_number'] }}**
@endif

@component('mail::button', ['url' => $trackingUrl])
Semak Status Pesanan
@endcomponent

Pautan semakan ini sah selama 90 hari. Simpan e-mel ini untuk rujukan.

Terima kasih,\
POLIBEST
@endcomponent
