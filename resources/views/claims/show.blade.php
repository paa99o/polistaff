@extends('layouts.app', ['title' => 'Semakan Tuntutan'])

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h1 class="h4">{{ $claim->title }}</h1>
                <span class="badge {{ \App\Support\PolistaffLabels::statusClass($claim->status) }}">{{ \App\Support\PolistaffLabels::status($claim->status) }}</span>
                <hr>
                <p>{{ $claim->description }}</p>
                <dl class="row">
                    <dt class="col-sm-4">Jumlah</dt><dd class="col-sm-8">RM {{ number_format((float) $claim->amount, 2) }}</dd>
                    <dt class="col-sm-4">Baki Kelab Tersedia</dt><dd class="col-sm-8">RM {{ number_format($availableBalance, 2) }}</dd>
                    <dt class="col-sm-4">Kategori</dt><dd class="col-sm-8">{{ $claim->category }}</dd>
                    <dt class="col-sm-4">Tarikh Tuntutan</dt><dd class="col-sm-8">{{ $claim->claim_date->format('d/m/Y') }}</dd>
                    <dt class="col-sm-4">Disahkan Bendahari</dt><dd class="col-sm-8">{{ $claim->treasurerVerifier->name ?? '-' }} @if($claim->treasurer_verified_at)({{ $claim->treasurer_verified_at->format('d/m/Y H:i') }})@endif</dd>
                    <dt class="col-sm-4">Catatan Bendahari</dt><dd class="col-sm-8">{{ $claim->treasurer_notes ?? '-' }}</dd>
                    <dt class="col-sm-4">Resit</dt><dd class="col-sm-8"><a href="{{ route('claims.receipt', $claim) }}" target="_blank">Lihat Resit</a></dd>
                    <dt class="col-sm-4">Transaksi</dt><dd class="col-sm-8">@if($claim->transaction)<a href="{{ route('transactions.show', $claim->transaction) }}">{{ $claim->transaction->receipt_number }}</a>@else - @endif</dd>
                </dl>
                @if(auth()->id() === $claim->user_id && $claim->status === 'pending')
                    <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-outline-danger" href="{{ route('claims.edit', $claim) }}">Sunting Tuntutan</a>
                        <form method="post" action="{{ route('claims.destroy', $claim) }}" data-confirm="Batalkan tuntutan ini? Rekod tuntutan akan dipadam.">
                            @csrf
                            @method('delete')
                            <button class="btn btn-outline-secondary" type="submit">Padam Tuntutan</button>
                        </form>
                    </div>
                @elseif(auth()->id() === $claim->user_id && $claim->status === 'rejected')
                    <a class="btn btn-danger" href="{{ route('claims.resubmit.form', $claim) }}">Hantar Semula Tuntutan</a>
                @endif
            </div>
        </div>

        @if(auth()->user()->hasRole('treasurer','chairman','admin'))
            @include('partials.audit-timeline', ['logs' => $timelineLogs])
        @endif
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 soft-panel-title">Kelulusan</h2>
                @if(auth()->user()->hasRole('treasurer','admin') && $claim->status === 'pending')
                    <form method="post" action="{{ route('claims.verify', $claim) }}" class="mb-3" data-confirm="Sahkan tuntutan ini sebagai bendahari?">
                        @csrf
                        @method('patch')
                        <label class="form-label" for="treasurer_notes">Catatan Bendahari</label>
                        <textarea class="form-control @error('treasurer_notes') is-invalid @enderror mb-2" id="treasurer_notes" name="treasurer_notes" rows="3" placeholder="Catatan bendahari">{{ old('treasurer_notes') }}</textarea>
                        @include('partials.errors', ['name' => 'treasurer_notes'])
                        <button class="btn btn-danger w-100">Sahkan sebagai Bendahari</button>
                    </form>
                @endif

                @if(auth()->user()->hasRole('chairman','admin') && $claim->status === 'treasurer_verified')
                    <form method="post" action="{{ route('claims.approve', $claim) }}" class="mb-3" data-confirm="Luluskan tuntutan dan jana transaksi perbelanjaan?">
                        @csrf
                        @method('patch')
                        <label class="form-label" for="approve_review_notes">Catatan Kelulusan</label>
                        <textarea class="form-control @error('review_notes') is-invalid @enderror mb-2" id="approve_review_notes" name="review_notes" rows="3" placeholder="Catatan">{{ old('review_notes') }}</textarea>
                        @include('partials.errors', ['name' => 'review_notes'])
                        <button class="btn btn-danger w-100">Luluskan Tuntutan</button>
                    </form>
                @endif

                @if(auth()->user()->hasRole('chairman','admin') && in_array($claim->status, ['pending','treasurer_verified'], true))
                    <form method="post" action="{{ route('claims.reject', $claim) }}" data-confirm="Tolak tuntutan ini? Emel akan dihantar kepada ahli.">
                        @csrf
                        @method('patch')
                        <label class="form-label" for="reject_review_notes">Sebab Ditolak</label>
                        <textarea class="form-control @error('review_notes') is-invalid @enderror mb-2" id="reject_review_notes" name="review_notes" rows="3" required placeholder="Sebab tuntutan ditolak">{{ old('review_notes') }}</textarea>
                        @include('partials.errors', ['name' => 'review_notes'])
                        <button class="btn btn-outline-secondary w-100">Tolak Tuntutan</button>
                    </form>
                @endif

                @unless(in_array($claim->status, ['pending','treasurer_verified'], true))
                    <p class="text-muted">Tiada tindakan tersedia.</p>
                @endunless
            </div>
        </div>
    </div>
</div>
@endsection
