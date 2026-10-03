@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <p class="text-uppercase small fw-bold text-muted mb-1">PoliMart</p>
            <h1 class="h3 mb-0">Pesanan PoliMart</h1>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Pesanan</th>
                        <th>Pembeli</th>
                        <th>Item</th>
                        <th>Jumlah</th>
                        <th>Bayaran</th>
                        <th>Status pesanan</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td><strong>{{ $order->order_number }}</strong><div class="small text-muted">{{ $order->created_at->format('d/m/Y h:i A') }}</div></td>
                        <td><strong>{{ $order->customer_name }}</strong><div class="small text-muted">{{ $order->customer_email }}<br>{{ $order->customer_phone }}</div></td>
                        <td>
                            @foreach($order->items as $item)
                                <div>{{ $item['name'] ?? 'Produk' }} × {{ $item['quantity'] ?? 0 }}</div>
                            @endforeach
                        </td>
                        <td>RM {{ number_format((float) $order->total, 2) }}</td>
                        <td>
                            @php
                                $paymentLabels = [
                                    'awaiting_payment' => 'Menunggu bayaran',
                                    'proof_submitted' => 'Bukti dihantar',
                                    'paid' => 'Bayaran disahkan',
                                    'rejected' => 'Perlu bukti semula',
                                    'expired' => 'Pesanan luput',
                                    'cancelled' => 'Dibatalkan',
                                    'refund_required' => 'Perlu pulangan wang',
                                    'refunded' => 'Wang dipulangkan',
                                ];
                            @endphp
                            <strong>{{ $paymentLabels[$order->payment_status] ?? ucfirst(str_replace('_', ' ', $order->payment_status)) }}</strong>
                            @if($order->payment_expires_at && $order->payment_status === 'awaiting_payment')
                                <div class="small text-muted">Luput: {{ $order->payment_expires_at->format('d/m/Y h:i A') }}</div>
                            @endif
                            @if($order->payment_review_note)<div class="small text-danger">{{ $order->payment_review_note }}</div>@endif
                            <div class="small text-muted">
                                {{ $order->payment_method === 'qr' ? 'QR' : ($order->payment_method === 'fpx' ? 'FPX · '.($order->payment_instructions['fpx_bank'] ?? 'Bank') : 'Pindahan bank') }}
                                {{ $order->payment_reference ? ' · '.$order->payment_reference : '' }}
                            </div>
                            @if($order->payment_proof_path)
                                <a class="btn btn-sm btn-outline-secondary mt-1" href="{{ route('admin.polimart.orders.payment-proof', $order) }}" target="_blank" rel="noopener">Lihat bukti</a>
                            @endif
                        </td>
                        <td>{{ ['pending' => 'Menunggu', 'confirmed' => 'Diproses', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'][$order->status] ?? ucfirst($order->status) }}</td>
                        <td>
                            @if($order->payment_status === 'proof_submitted')
                                <form method="post" action="{{ route('admin.polimart.orders.payment-confirm', $order) }}" class="mb-2">
                                    @csrf @method('patch')
                                    <button class="btn btn-sm btn-success" type="submit">Sahkan bayaran</button>
                                </form>
                                <form method="post" action="{{ route('admin.polimart.orders.payment-reject', $order) }}" class="mb-2">
                                    @csrf @method('patch')
                                    <label class="form-label small" for="payment-note-{{ $order->id }}">Sebab minta bukti semula</label>
                                    <textarea class="form-control form-control-sm mb-1" id="payment-note-{{ $order->id }}" name="payment_review_note" rows="2" maxlength="1000" required></textarea>
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Minta bukti baharu</button>
                                </form>
                            @endif
                            @if($order->payment_status === 'refund_required')
                                <form method="post" action="{{ route('admin.polimart.orders.refund-confirm', $order) }}" class="mb-2">
                                    @csrf @method('patch')
                                    <button class="btn btn-sm btn-outline-warning" type="submit">Tandakan wang dipulangkan</button>
                                </form>
                            @endif
                            @if($order->status === 'pending' || $order->status === 'confirmed')
                                <div class="d-flex flex-wrap gap-2">
                                    @if($order->status === 'pending')
                                        <form method="post" action="{{ route('admin.polimart.orders.update', $order) }}">
                                            @csrf @method('patch')
                                            <input type="hidden" name="status" value="confirmed">
                                            <button class="btn btn-sm btn-outline-primary" type="submit" @disabled($order->payment_status !== 'paid')>Proses pesanan</button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('admin.polimart.orders.update', $order) }}">
                                            @csrf @method('patch')
                                            <input type="hidden" name="status" value="completed">
                                            <button class="btn btn-sm btn-outline-success" type="submit">Tandakan selesai</button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('admin.polimart.orders.update', $order) }}">
                                        @csrf @method('patch')
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Batal</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pesanan PoliMart.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
