@extends('layouts.app', ['title' => 'Penyata Yuran'])

@section('content')
@php
    $billStatusLabels = ['unpaid' => 'Belum Dibayar', 'partial' => 'Sebahagian', 'paid' => 'Selesai', 'overdue' => 'Tertunggak'];
    $paymentStatusLabels = ['pending' => 'Menunggu', 'approved' => 'Diluluskan', 'rejected' => 'Ditolak'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0">Penyata Yuran</h1>
        <p class="text-muted mb-0">Ringkasan caj bulanan, bayaran dan baki tertunggak.</p>
    </div>
    <a class="btn btn-outline-danger" href="{{ route('payments.index') }}">Kembali</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Baki Tertunggak</div><div class="stat-value">RM {{ number_format((float) $balance, 2) }}</div></div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Bil Belum Selesai</div><div class="stat-value">{{ $bills->whereIn('status', ['unpaid', 'partial', 'overdue'])->count() }}</div></div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="stat-label">Bayaran Terkini</div><div class="stat-value">{{ $payments->count() }}</div></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5 soft-panel-title">Bil Bulanan</h2>
        <div class="table-responsive mt-3">
            <table class="table mobile-records align-middle mb-0">
                <thead><tr><th>Bulan</th><th>Tarikh Akhir</th><th>Caj</th><th>Dibayar</th><th>Baki</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($bills as $bill)
                    <tr>
                        <td data-label="Bulan">{{ $bill->billing_month->format('M Y') }}</td>
                        <td data-label="Tarikh Akhir">{{ $bill->due_date?->format('d/m/Y') ?? '-' }}</td>
                        <td data-label="Caj">RM {{ number_format((float) $bill->amount, 2) }}</td>
                        <td data-label="Dibayar">RM {{ number_format((float) $bill->paid_amount, 2) }}</td>
                        <td data-label="Baki">RM {{ number_format($bill->remainingAmount(), 2) }}</td>
                        <td data-label="Status"><span class="badge bg-secondary">{{ $billStatusLabels[$bill->status] ?? ucfirst($bill->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada bil bulanan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $bills->links() }}</div>

<div class="card">
    <div class="card-body">
        <h2 class="h5 soft-panel-title">Sejarah Bayaran Terkini</h2>
        <div class="table-responsive mt-3">
            <table class="table mobile-records align-middle mb-0">
                <thead><tr><th>Tarikh</th><th>Jumlah</th><th>Diperuntukkan</th><th>Status</th><th>Resit</th></tr></thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td data-label="Tarikh">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td data-label="Jumlah">RM {{ number_format((float) $payment->amount, 2) }}</td>
                        <td data-label="Diperuntukkan">RM {{ number_format((float) $payment->allocated_amount, 2) }}</td>
                        <td data-label="Status"><span class="badge bg-secondary">{{ $paymentStatusLabels[$payment->status] ?? ucfirst($payment->status) }}</span></td>
                        <td data-label="Resit">@if($payment->transaction)<a href="{{ route('transactions.show', $payment->transaction) }}">{{ $payment->transaction->receipt_number }}</a>@else - @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Belum ada sejarah bayaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
