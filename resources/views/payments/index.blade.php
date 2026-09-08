@extends('layouts.app', ['title' => 'Bayaran Yuran'])

@section('content')
@php
    $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Diluluskan', 'rejected' => 'Ditolak'];
    $statusClasses = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0">Bayaran Yuran</h1>
        <p class="text-muted mb-0">Hantar bukti bayaran dan semak status kelulusan bendahari.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-danger" href="{{ route('payments.statement') }}">Penyata Yuran</a>
        <a class="btn btn-danger" href="{{ route('payments.create') }}">
            <i class="bi bi-upload me-2" aria-hidden="true"></i>Muat Naik Bukti
        </a>
    </div>
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
                        <td data-label="Tindakan"><a class="btn btn-sm btn-outline-danger" href="{{ route('payments.show', $payment) }}">Semak</a></td>
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
@endsection
