@extends('layouts.app', ['title' => 'Belian Saya'])

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="dashboard-kicker mb-1">PoliMart</p>
        <h1 class="h3 mb-0">Belian Saya</h1>
        <p class="text-muted mb-0">Semak pesanan yang dibuat menggunakan akaun ini.</p>
    </div>
    <a class="btn btn-outline-primary" href="{{ route('polimart.index') }}">Teruskan membeli</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombor Pesanan</th>
                    <th>Tarikh</th>
                    <th>Jumlah</th>
                    <th>Bayaran</th>
                    <th>Status Pesanan</th>
                    <th>Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td><strong>{{ $order->order_number }}</strong></td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>RM {{ number_format((float) $order->total, 2) }}</td>
                        <td>{{ [
                            'awaiting_payment' => 'Menunggu bayaran',
                            'proof_submitted' => 'Bukti dihantar',
                            'paid' => 'Dibayar',
                            'rejected' => 'Bukti ditolak',
                            'expired' => 'Luput',
                            'cancelled' => 'Dibatalkan',
                            'refund_required' => 'Pemulangan diperlukan',
                            'refunded' => 'Dipulangkan',
                        ][$order->payment_status] ?? $order->payment_status }}</td>
                        <td>{{ [
                            'pending' => 'Menunggu',
                            'confirmed' => 'Diproses',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                        ][$order->status] ?? $order->status }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ $trackingUrls[$order->id] }}">Lihat pesanan</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="dashboard-empty-state my-3"><span class="stat-icon"><i class="bi bi-bag" aria-hidden="true"></i></span><p class="text-muted mb-0">Belum ada pembelian yang dipautkan kepada akaun ini.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $orders->links() }}</div>
<p class="small text-muted mt-3">Pesanan terdahulu yang dibuat sebagai tetamu boleh disemak melalui pautan yang dihantar ke e-mel semasa checkout.</p>
@endsection
