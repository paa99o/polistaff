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
                    <span class="badge {{ \App\Support\PolistaffLabels::statusClass($payment->status) }}">{{ \App\Support\PolistaffLabels::status($payment->status) }}</span>
                </div>
                <hr>
                <dl class="row">
                    <dt class="col-sm-4">Jumlah</dt><dd class="col-sm-8">RM {{ number_format((float) $payment->amount, 2) }}</dd>
                    <dt class="col-sm-4">Diperuntukkan kepada Bil</dt><dd class="col-sm-8">RM {{ number_format((float) $payment->allocated_amount, 2) }}</dd>
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

        @if(auth()->user()->hasRole('treasurer','chairman','admin'))
            @include('partials.audit-timeline', ['logs' => $timelineLogs])
        @endif
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 soft-panel-title">Semakan Bendahari</h2>
                @if(auth()->user()->hasRole('treasurer','admin') && $payment->status === 'pending')
                    <form method="post" action="{{ route('payments.approve', $payment) }}" class="mb-3" data-confirm="Luluskan bayaran dan jana resit?">
                        @csrf
                        @method('patch')
                        <label class="form-label" for="approve-review-notes">Catatan Kelulusan</label>
                        <textarea class="form-control @error('review_notes') is-invalid @enderror mb-2" id="approve-review-notes" name="review_notes" rows="3" placeholder="Pilihan">{{ old('review_notes') }}</textarea>
                        @include('partials.errors', ['name' => 'review_notes'])
                        <button class="btn btn-danger w-100">Luluskan dan Jana Resit</button>
                    </form>
                    <form method="post" action="{{ route('payments.reject', $payment) }}" data-confirm="Tolak bayaran ini? Emel akan dihantar kepada ahli.">
                        @csrf
                        @method('patch')
                        <label class="form-label" for="reject-review-notes">Sebab Ditolak</label>
                        <textarea class="form-control @error('review_notes') is-invalid @enderror mb-2" id="reject-review-notes" name="review_notes" rows="3" required>{{ old('review_notes') }}</textarea>
                        @include('partials.errors', ['name' => 'review_notes'])
                        <button class="btn btn-outline-secondary w-100">Tolak Bayaran</button>
                    </form>
                @else
                    <p class="text-muted mb-0">Tiada tindakan diperlukan.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
