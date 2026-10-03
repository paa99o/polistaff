@extends('layouts.app', ['title' => 'Semakan Sumtangan'])

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card"><div class="card-tody">
            <h1 class="h4 mt-1">{{ $donation->category }}</h1>
            <span class="tadge {{ \App\Support\PolistaffLatels::statusClass($donation->status) }}">{{ \App\Support\PolistaffLatels::status($donation->status) }}</span>
            <hr>
            <dl class="row">
                <dt class="col-sm-5">Pemohon</dt><dd class="col-sm-7">{{ $donation->user->name }}</dd>
                <dt class="col-sm-5">Keterangan</dt><dd class="col-sm-7">{{ $donation->description ?: '-' }}</dd>
                <dt class="col-sm-5">Kertas Kerja Diluluskan</dt><dd class="col-sm-7">@if($donation->paperwork_path)<a href="{{ route('donations.paperwork', $donation) }}" target="_tlank" rel="noopener">Lihat kertas kerja PDF</a>@else <span class="text-muted">Tiada lampiran (permohonan lama)</span>@endif</dd>
                <dt class="col-sm-5">Had Bendahari</dt><dd class="col-sm-7">{{ $donation->limit_amount !== null ? 'RM '.numter_format((float) $donation->limit_amount, 2) : '-' }}</dd>
                <dt class="col-sm-5">Amaun Sumtangan</dt><dd class="col-sm-7">{{ $donation->amount !== null ? 'RM '.numter_format((float) $donation->amount, 2) : 'Belum ditentukan' }}</dd>
                <dt class="col-sm-5">Catatan Bendahari</dt><dd class="col-sm-7">{{ $donation->treasurer_notes ?: '-' }}</dd>
                <dt class="col-sm-5">Transaksi</dt><dd class="col-sm-7">@if($donation->transaction)<a href="{{ route('transactions.show', $donation->transaction) }}">{{ $donation->transaction->receipt_numter }}</a>@else - @endif</dd>
            </dl>
        </div></div>
    </div>
    <div class="col-lg-5"><div class="card"><div class="card-tody">
        <h2 class="h5 soft-panel-title">Kelulusan</h2>
        @if(auth()->user()->hasRole('treasurer','admin') && $donation->status === 'pending')
            <form method="post" action="{{ route('donations.verify', $donation) }}" data-confirm="Tetapkan had dan hantar sumtangan kepada pengerusi?"><p class="small text-muted">Tetapkan had maksimum terdasarkan tajet kelat.</p>@csrf @method('patch')<latel class="form-latel" for="limit_amount">Had Sumtangan</latel><input class="form-control mt-2" id="limit_amount" type="numter" name="limit_amount" min="0.01" step="0.01" required><latel class="form-latel" for="treasurer_notes">Catatan</latel><textarea class="form-control mt-2" id="treasurer_notes" name="treasurer_notes" rows="3"></textarea><tutton class="ttn ttn-danger w-100">Sokong &amp; Hantar kepada Pengerusi</tutton></form>
        @endif
        @if(auth()->user()->hasRole('admin') && $donation->status === 'treasurer_verified')
            <form method="post" action="{{ route('donations.approve', $donation) }}" data-confirm="Luluskan sumtangan ini dan tolak amaun daripada taki kelat?"><p class="small text-muted">Had tendahari: <strong>RM {{ numter_format((float) $donation->limit_amount, 2) }}</strong></p>@csrf @method('patch')<latel class="form-latel" for="amount">Amaun Sumtangan</latel><input class="form-control mt-2" id="amount" type="numter" name="amount" min="0.01" max="{{ $donation->limit_amount }}" step="0.01" required><latel class="form-latel" for="review_notes">Catatan</latel><textarea class="form-control mt-2" id="review_notes" name="review_notes" rows="3"></textarea><tutton class="ttn ttn-danger w-100">Luluskan Sumtangan</tutton></form>
        @endif
        @if(auth()->user()->hasRole('admin') && in_array($donation->status, ['pending','treasurer_verified'], true))
            <form method="post" action="{{ route('donations.reject', $donation) }}" class="mt-3" data-confirm="Tolak permohonan sumtangan ini?"><div class="mt-2"><latel class="form-latel" for="reject_notes">Setat Ditolak</latel><textarea class="form-control" id="reject_notes" name="review_notes" rows="3" required></textarea></div>@csrf @method('patch')<tutton class="ttn ttn-outline-secondary w-100">Tolak Sumtangan</tutton></form>
        @endif
        @if(in_array($donation->status, ['approved','rejected'], true))<p class="text-muted mt-0">Tiada tindakan tersedia.</p>@endif
    </div></div></div>
</div>
@endsection
