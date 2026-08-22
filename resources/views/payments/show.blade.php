@extends('layouts.app', ['title' => 'Semakan Bayaran'])

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="h4 mb-1">Semakan Bayaran #{{ $payment->id }}</h1>
                        <p class="text-muted mb-0">Dihantar oleh {{ $payment->user->name }}</p>
                    </div>
                    <span class="badge bg-secondary">{{ $payment->status }}</span>
                </div>
                <hr>
                <dl class="row">
                    <dt class="col-sm-4">Jumlah</dt><dd class="col-sm-8">RM {{ number_format((float) $payment->amount, 2) }}</dd>
                    <dt class="col-sm-4">Allocated to Bills</dt><dd class="col-sm-8">RM {{ number_format((float) $payment->allocated_amount, 2) }}</dd>
                    <dt class="col-sm-4">Kaedah</dt><dd class="col-sm-8">{{ $payment->payment_method }}</dd>
                    <dt class="col-sm-4">Tarikh Bayaran</dt><dd class="col-sm-8">{{ $payment->payment_date->format('d/m/Y') }}</dd>
                    <dt class="col-sm-4">Catatan Ahli</dt><dd class="col-sm-8">{{ $payment->notes ?? '-' }}</dd>
                    <dt class="col-sm-4">Disemak Oleh</dt><dd class="col-sm-8">{{ $payment->reviewer->name ?? '-' }}</dd>
                    <dt class="col-sm-4">Catatan Semakan</dt><dd class="col-sm-8">{{ $payment->review_notes ?? '-' }}</dd>
                    <dt class="col-sm-4">Resit</dt><dd class="col-sm-8">@if($payment->transaction)<a href="{{ route('transactions.show', $payment->transaction) }}">{{ $payment->transaction->receipt_number }}</a>@else - @endif</dd>
                </dl>
                <a class="btn btn-outline-danger" target="_blank" href="{{ route('payments.proof', $payment) }}">Lihat Bukti Bayaran</a>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 soft-panel-title">Treasurer Review</h2>
                @if(auth()->user()->hasRole('treasurer','chairman','admin') && $payment->status === 'pending')
                    <form method="post" action="{{ route('payments.approve', $payment) }}" class="mb-3">
                        @csrf @method('patch')
                        <label class="form-label">Catatan Kelulusan</label>
                        <textarea class="form-control mb-2" name="review_notes" rows="3" placeholder="Optional"></textarea>
                        <button class="btn btn-danger w-100">Approve & Generate Receipt</button>
                    </form>
                    <form method="post" action="{{ route('payments.reject', $payment) }}">
                        @csrf @method('patch')
                        <label class="form-label">Sebab Ditolak</label>
                        <textarea class="form-control mb-2" name="review_notes" rows="3" required></textarea>
                        <button class="btn btn-outline-secondary w-100">Reject Payment</button>
                    </form>
                @else
                    <p class="text-muted mb-0">Tiada tindakan diperlukan.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
