@extends('layouts.public', ['title' => 'Status Pesanan PoliMart'])

@section('content')
<section class="public-container public-success-section">
    <div class="public-success-card">
        <p class="public-eyebrow">Semakan pesanan</p>
        <h1>Status pesanan</h1>
        <strong class="public-order-number">{{ $order->order_number }}</strong>
        <p class="mt-3 mb-1">Status: <strong>{{ ucfirst($order->status) }}</strong></p>
        <p class="mb-1">Status bayaran: <strong>{{ ['awaiting_payment' => 'Menunggu bayaran', 'proof_submitted' => 'Bukti dihantar, menunggu semakan', 'paid' => 'Bayaran disahkan', 'rejected' => 'Bukti perlu dihantar semula', 'expired' => 'Pesanan luput', 'cancelled' => 'Pesanan dibatalkan', 'refund_required' => 'Bayaran perlu dipulangkan oleh penjual', 'refunded' => 'Bayaran ditandakan telah dipulangkan'][$order->payment_status] ?? $order->payment_status }}</strong></p>
        @if($order->payment_expires_at && $order->payment_status === 'awaiting_payment')<p class="small text-muted">Sila bayar sebelum {{ $order->payment_expires_at->format('d/m/Y h:i A') }}. Pesanan akan dibatalkan selepas tempoh ini.</p>@endif
        @if($order->payment_review_note)<div class="alert alert-warning text-start">Penjual meminta bukti baharu: {{ $order->payment_review_note }}</div>@endif
        <p class="text-muted">Dihantar pada {{ $order->created_at->format('d/m/Y h:i A') }}</p>

        <div class="text-start my-4">
            <h2 class="h5">Item</h2>
            @foreach($order->items as $item)
                <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                    <span>{{ $item['name'] }} × {{ $item['quantity'] }}</span>
                    <strong>RM {{ number_format((float) $item['price'] * (int) $item['quantity'], 2) }}</strong>
                </div>
            @endforeach
            <div class="d-flex justify-content-between gap-3 pt-3">
                <span>Jumlah termasuk penghantaran</span>
                <strong>RM {{ number_format((float) $order->total, 2) }}</strong>
            </div>
        </div>

        <div class="text-start mb-4">
            @if(in_array($order->payment_status, ['awaiting_payment', 'rejected'], true))
                <h2 class="h5">Bayaran terus kepada penjual</h2>
                @if(($order->payment_instructions['method'] ?? null) === 'qr')
                <p>Imbas QR ini menggunakan aplikasi bank atau e-wallet anda. Selepas membayar, muat naik bukti di bawah. Penjual akan menyemak transaksi sebenar.</p>
                <img class="d-block mb-3" src="{{ asset('storage/'.$order->payment_instructions['qr_code_path']) }}" alt="QR bayaran penjual" style="max-width: 240px; max-height: 240px">
                <a class="btn btn-sm btn-outline-secondary mb-3" href="{{ asset('storage/'.$order->payment_instructions['qr_code_path']) }}" download>Simpan imej QR</a>
                @if($order->payment_instructions['account_name'] ?? null)<p>Nama penerima: <strong>{{ $order->payment_instructions['account_name'] }}</strong></p>@endif
                @elseif(($order->payment_instructions['method'] ?? null) === 'bank_transfer')
                <p>Buat pindahan menggunakan aplikasi bank anda, kemudian muat naik bukti di bawah. Penjual akan menyemak transaksi sebenar.</p>
                <p>{{ $order->payment_instructions['bank_name'] }}<br>{{ $order->payment_instructions['account_name'] }}<br><strong>{{ $order->payment_instructions['account_number'] }}</strong></p>
                @elseif(($order->payment_instructions['method'] ?? null) === 'fpx')
                <p>Bank pilihan: <strong>{{ $order->payment_instructions['fpx_bank'] ?? '-' }}</strong>. Ini bukan gateway FPX; buat pindahan melalui aplikasi bank anda dan muat naik bukti. Penjual akan menyemak transaksi sebenar.</p>
                <p>{{ $order->payment_instructions['bank_name'] }}<br>{{ $order->payment_instructions['account_name'] }}<br><strong>{{ $order->payment_instructions['account_number'] }}</strong></p>
                @endif
            @endif
            @forelse($sellerContacts as $sellerContact)
                @if($sellerContact['url'])
                    <a class="btn btn-outline-success" href="{{ $sellerContact['url'] }}" target="_blank" rel="noopener">Hubungi penjual melalui WhatsApp</a>
                @else
                    <p>Nombor penjual: {{ $sellerContact['contact'] }}</p>
                @endif
            @empty
                <p class="text-muted">Maklumat hubungan penjual tiada dalam listing ini.</p>
            @endforelse
        </div>

        @if(in_array($order->payment_status, ['awaiting_payment', 'rejected'], true))
            <form class="text-start border rounded p-3 mb-4" method="post" action="{{ $proofSubmitUrl }}" enctype="multipart/form-data">
                @csrf
                <h2 class="h6">Hantar bukti bayaran</h2>
                <p class="small text-muted">Bukti yang dihantar bukan pengesahan automatik. Penjual akan semak transaksi dalam akaun bank.</p>
                <label class="form-label" for="payment_proof">Resit atau tangkap layar (JPG, PNG atau PDF, maksimum 5 MB)</label>
                <input class="form-control mb-2" id="payment_proof" type="file" name="payment_proof" accept="image/jpeg,image/png,application/pdf" required>
                <label class="form-label" for="payment_reference">Nombor rujukan transaksi (pilihan)</label>
                <input class="form-control mb-3" id="payment_reference" name="payment_reference" maxlength="120">
                <button class="btn btn-primary" type="submit">Hantar bukti</button>
            </form>
        @elseif($order->payment_status === 'proof_submitted')
            <div class="alert alert-info text-start">Bukti anda sudah dihantar. Pesanan hanya boleh diproses selepas penjual menyemak dan mengesahkan bayaran.</div>
        @elseif($order->payment_status === 'refund_required')
            <div class="alert alert-warning text-start">Penjual perlu memulangkan bayaran secara manual. Hubungi penjual untuk urusan pemulangan.</div>
        @endif

        <a class="btn btn-primary" href="{{ route('polimart.index') }}">Kembali ke PoliMart</a>
    </div>
</section>
@endsection
