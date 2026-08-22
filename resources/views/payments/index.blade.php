@extends('layouts.app', ['title' => 'Bayaran Yuran'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Bayaran Yuran</h1>
        <p class="text-muted mb-0">Hantar bukti bayaran dan semak status kelulusan bendahari.</p>
    </div>
    <div class="d-flex gap-2"><a class="btn btn-outline-danger" href="{{ route('payments.statement') }}">Penyata Yuran</a><a class="btn btn-danger" href="{{ route('payments.create') }}">Upload Bukti Bayaran</a></div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h5 soft-panel-title">Senarai Bayaran</h2>
        <form class="row g-2 mt-3"><div class="col-md-3"><select class="form-select" name="status"><option value="">All Status</option>@foreach(['pending','approved','rejected'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ $status }}</option>@endforeach</select></div><div class="col-md-3"><input class="form-control" type="date" name="from" value="{{ request('from') }}"></div><div class="col-md-3"><input class="form-control" type="date" name="to" value="{{ request('to') }}"></div><div class="col-md-3"><button class="btn btn-outline-danger w-100">Filter</button></div></form>
        <div class="table-responsive mt-3">
            <table class="table mobile-records align-middle mb-0">
                <thead><tr><th>Ahli</th><th>Jumlah</th><th>Allocated</th><th>Kaedah</th><th>Tarikh Bayaran</th><th>Status</th><th>Resit</th><th></th></tr></thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td data-label="Ahli"><strong>{{ $payment->user->name }}</strong><div class="small text-muted">{{ $payment->user->email }}</div></td>
                        <td data-label="Jumlah">RM {{ number_format((float) $payment->amount, 2) }}</td>
                        <td data-label="Allocated">RM {{ number_format((float) $payment->allocated_amount, 2) }}</td>
                        <td data-label="Kaedah">{{ $payment->payment_method }}</td>
                        <td data-label="Tarikh">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td data-label="Status"><span class="badge bg-secondary">{{ $payment->status }}</span></td>
                        <td data-label="Resit">@if($payment->transaction)<a href="{{ route('transactions.show', $payment->transaction) }}">{{ $payment->transaction->receipt_number }}</a>@else<span class="text-muted">-</span>@endif</td>
                        <td data-label="Tindakan"><a class="btn btn-sm btn-outline-danger" href="{{ route('payments.show', $payment) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">Tiada rekod bayaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
