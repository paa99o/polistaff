@extends('layouts.app', ['title' => 'Bayaran Yuran'])

@section('content')
@php
    $canSubmitPayment = auth()->user()->hasRole('member');
    $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Diluluskan', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'];
    $statusClasses = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0">Bayaran Yuran</h1>
        <p class="text-muted mb-0">{{ $isFinanceManager ? 'Pantau bayaran yuran ahli dan semak bukti yang menunggu tindakan.' : 'Hantar bukti bayaran dan semak status kelulusan bendahari.' }}</p>
    </div>
    @if($canSubmitPayment)
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-danger" href="{{ route('payments.create') }}">
                <i class="bi bi-wallet2 me-2" aria-hidden="true"></i>Bayar Yuran
            </a>
        </div>
    @endif
</div>

<div class="row g-3 mb-4 payment-summary-cards">
    @if($isFinanceManager)
        <div class="col-md-4">
            <a class="admin-stat-card payment-fee-stat-card w-100 text-start" href="{{ route('payments.index', ['status' => 'pending']) }}">
                <span class="admin-stat-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                <strong>{{ $pendingPaymentCount }}</strong>
                <span>Bayaran Menunggu Semakan</span>
                <small class="text-muted">Bukti bayaran ahli yang perlu diproses</small>
            </a>
        </div>
        <div class="col-md-4">
            <a class="admin-stat-card payment-fee-stat-card w-100 text-start" href="{{ route('transactions.index', ['type' => 'income', 'category' => 'Yuran', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]) }}">
                <span class="admin-stat-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
                <strong>RM {{ number_format((float) $collectionThisMonth, 2) }}</strong>
                <span>Jumlah Kutipan Bulan Ini</span>
                <small class="text-muted">Kutipan yuran ahli yang direkodkan</small>
            </a>
        </div>
        <div class="col-md-4">
            <a class="admin-stat-card payment-fee-stat-card w-100 text-start" href="{{ route('finance.fees.index') }}">
                <span class="admin-stat-icon"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span>
                <strong>RM {{ number_format((float) $outstandingTotal, 2) }}</strong>
                <span>Jumlah Tunggakan Ahli</span>
                <small class="text-muted">Baki yuran ahli aktif yang belum selesai</small>
            </a>
        </div>
    @else
    <div class="col-md-4">
        <button class="admin-stat-card payment-fee-stat-card w-100 text-start" type="button" data-bs-toggle="modal" data-bs-target="#overdueFeesModal">
            <span class="admin-stat-icon"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span>
            <strong>RM {{ number_format((float) $overdueBills->sum(fn ($bill) => $bill->remainingAmount()), 2) }}</strong>
            <span>Bayaran Tertunggak</span>
            <small class="text-muted">{{ $overdueBills->count() }} bulan tahun sebelumnya</small>
        </button>
    </div>
    <div class="col-md-4">
        <button class="admin-stat-card payment-fee-stat-card w-100 text-start" type="button" data-bs-toggle="modal" data-bs-target="#recentFeesModal">
            <span class="admin-stat-icon"><i class="bi bi-check-circle" aria-hidden="true"></i></span>
            <strong>{{ $recentApprovedPayments->count() }}</strong>
            <span>Bayaran Terkini</span>
            <small class="text-muted">{{ $recentApprovedPayments->first()?->payment_date?->translatedFormat('F Y') ?? 'Belum ada bayaran selesai' }}</small>
        </button>
    </div>
    <div class="col-md-4">
        <button class="admin-stat-card payment-fee-stat-card w-100 text-start" type="button" data-bs-toggle="modal" data-bs-target="#unpaidFeesModal">
            <span class="admin-stat-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
            <strong>RM {{ number_format((float) $currentUnpaidBills->sum(fn ($bill) => $bill->remainingAmount()), 2) }}</strong>
            <span>Belum Dibayar sehingga {{ now()->translatedFormat('F Y') }}</span>
            <small class="text-muted">{{ $currentUnpaidBills->count() }} bulan belum selesai</small>
        </button>
    </div>
    @endif
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h5 soft-panel-title">Senarai Bayaran</h2>
        <form class="row g-2 mt-3">
            <div class="col-md-3">
                <label class="form-label" for="payment-status">Status</label>
                <select class="form-select" id="payment-status" name="status">
                    <option value="">Semua status</option>
                    @foreach($statusLabels as $status => $label)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="payment-from">Dari</label>
                <input class="form-control" id="payment-from" type="date" name="from" value="{{ request('from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="payment-to">Hingga</label>
                <input class="form-control" id="payment-to" type="date" name="to" value="{{ request('to') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-outline-danger w-100">Tapis</button>
            </div>
        </form>
        <div class="table-responsive mt-3">
            <table class="table mobile-records align-middle mb-0">
                <thead><tr><th>Ahli</th><th>Jumlah</th><th>Diperuntukkan</th><th>Kaedah</th><th>Tarikh Bayaran</th><th>Status</th><th>Resit</th><th>Tindakan</th></tr></thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td data-label="Ahli"><strong>{{ $payment->user->name }}</strong><div class="small text-muted">{{ $payment->user->email }}</div></td>
                        <td data-label="Jumlah">RM {{ number_format((float) $payment->amount, 2) }}</td>
                        <td data-label="Diperuntukkan">RM {{ number_format((float) $payment->allocated_amount, 2) }}</td>
                        <td data-label="Kaedah">{{ $payment->payment_method }}</td>
                        <td data-label="Tarikh">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td data-label="Status"><span class="badge bg-{{ $statusClasses[$payment->status] ?? 'secondary' }}">{{ $statusLabels[$payment->status] ?? ucfirst($payment->status) }}</span></td>
                        <td data-label="Resit">@if($payment->transaction)<a href="{{ route('transactions.show', $payment->transaction) }}">{{ $payment->transaction->receipt_number }}</a>@else<span class="text-muted">-</span>@endif</td>
                        <td data-label="Tindakan"><a class="btn btn-sm btn-outline-danger" href="{{ route('payments.show', $payment) }}">Keterangan</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="dashboard-empty-state my-3">
                                <span class="stat-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
                                <p class="text-muted mb-0">Tiada rekod bayaran untuk tapisan ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>

@unless($isFinanceManager)
<div class="modal fade" id="overdueFeesModal" tabindex="-1" aria-labelledby="overdueFeesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h2 class="modal-title h5" id="overdueFeesTitle">Yuran tertunggak</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <p class="text-muted">Berikut ialah bulan tahun sebelumnya yang masih belum dibayar.</p>
                @forelse($overdueBills as $bill)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2"><span>{{ $bill->billing_month->translatedFormat('F Y') }}</span><strong>RM {{ number_format($bill->remainingAmount(), 2) }}</strong></div>
                @empty
                    <div class="alert alert-success mb-0">Tiada bayaran tertunggak.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="recentFeesModal" tabindex="-1" aria-labelledby="recentFeesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h2 class="modal-title h5" id="recentFeesTitle">Bayaran bulan yang telah selesai</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <p class="text-muted">Senarai bulan yuran yang telah dibayar dan masa bukti bayaran dihantar.</p>
                @forelse($paidBills as $bill)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-3 gap-3">
                        <div><strong>{{ $bill->billing_month->translatedFormat('F Y') }}</strong><div class="small text-muted">Dibayar pada {{ $bill->payment_datetime?->format('d/m/Y, h:i A') ?? 'Tarikh tidak direkodkan' }}</div></div>
                        <span class="badge bg-success">Selesai</span>
                    </div>
                @empty
                    <div class="alert alert-info mb-0">Belum ada bulan yuran yang selesai dibayar.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="unpaidFeesModal" tabindex="-1" aria-labelledby="unpaidFeesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h2 class="modal-title h5" id="unpaidFeesTitle">Bayaran yuran belum selesai</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <p class="text-muted">{{ $canSubmitPayment ? 'Pilih bulan yang ingin dibayar. Bukti pembayaran perlu dimuat naik selepas pilihan dibuat.' : 'Maklumat bulan yuran yang masih belum selesai.' }}</p>
                @forelse($currentUnpaidBills as $bill)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-3 gap-3">
                        <div><strong>{{ $bill->billing_month->translatedFormat('F Y') }}</strong><div class="small text-muted">Baki RM {{ number_format($bill->remainingAmount(), 2) }}</div></div>
                        @if($canSubmitPayment)
                            <a class="btn btn-sm btn-outline-danger" href="{{ route('payments.create', ['bill_id' => $bill->id]) }}">Bayar</a>
                        @endif
                    </div>
                @empty
                    <div class="alert alert-success mb-0">Tiada yuran belum dibayar sehingga bulan semasa.</div>
                @endforelse
            </div>
            @if($canSubmitPayment && $currentUnpaidBills->isNotEmpty())
                <div class="modal-footer"><a class="btn btn-danger" href="{{ route('payments.create') }}">Bayar &amp; upload bukti pembayaran</a></div>
            @endif
        </div>
    </div>
</div>
@endunless
@endsection
