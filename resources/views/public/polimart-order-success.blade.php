@extends('layouts.public', ['title' => 'Pesanan Diterima'])

@section('content')
<section class="public-container public-success-section">
    <div class="public-success-card">
        <span class="public-success-icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
        <p class="public-eyebrow">Pesanan diterima</p>
        <h1>Terima kasih, {{ $order->customer_name }}.</h1>
        <p>Pesanan anda telah direkodkan dan akan disemak oleh pihak POLIBEST. @if($trackingEmailSent) Pautan semakan turut dihantar ke e-mel anda.@else E-mel semakan tidak dapat dihantar, jadi simpan pautan semakan di bawah.@endif Pautan ini sah selama 90 hari.</p>
        <strong class="public-order-number">{{ $order->order_number }}</strong>

        <div class="text-start my-4">
            <h2 class="h5">Bayaran terus kepada penjual</h2>
            @if(($order->payment_instructions['method'] ?? null) === 'qr')
                <p>Imbas QR ini menggunakan aplikasi bank atau e-wallet anda. Selepas membayar, hubungi penjual untuk pengesahan.</p>
                <img class="d-block mb-3" src="{{ asset('storage/'.$order->payment_instructions['qr_code_path']) }}" alt="QR bayaran penjual" style="max-width: 240px; max-height: 240px">
                <a class="btn btn-sm btn-outline-secondary mb-3" href="{{ asset('storage/'.$order->payment_instructions['qr_code_path']) }}" download>Simpan imej QR</a>
                @if($order->payment_instructions['account_name'] ?? null)<p>Nama penerima: <strong>{{ $order->payment_instructions['account_name'] }}</strong></p>@endif
            @elseif(($order->payment_instructions['method'] ?? null) === 'bank_transfer')
                <p>Buat pindahan menggunakan aplikasi bank anda, kemudian hubungi penjual untuk pengesahan.</p>
                <p>{{ $order->payment_instructions['bank_name'] }}<br>{{ $order->payment_instructions['account_name'] }}<br><strong>{{ $order->payment_instructions['account_number'] }}</strong></p>
            @endif
            @forelse($sellerContacts as $sellerContact)
                <p class="mb-2">
                    @if($sellerContact['url'])
                        <a class="btn btn-outline-success" href="{{ $sellerContact['url'] }}" target="_blank" rel="noopener">Hubungi penjual melalui WhatsApp</a>
                    @else
                        <span>Nombor penjual: {{ $sellerContact['contact'] }}</span>
                    @endif
                </p>
            @empty
                <p class="text-muted">Maklumat hubungan penjual tiada dalam listing ini. Hubungi admin dengan nombor pesanan anda.</p>
            @endforelse
        </div>

        <div class="public-success-actions">
            <a class="btn btn-primary" href="{{ $trackingUrl }}">Semak Status Pesanan</a>
            <a class="btn btn-outline-primary" href="{{ route('polimart.index') }}">Teruskan Membeli</a>
            <a class="btn btn-outline-primary" href="{{ url('/') }}">Halaman Utama</a>
        </div>
    </div>
</section>
@endsection
