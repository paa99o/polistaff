@extends('layouts.app', ['title' => 'Pengurusan Yuran'])

@section('content')
@php($canManageFees = auth()->user()->hasRole('admin', 'treasurer'))

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <span class="section-kicker">Kewangan</span>
        <h1 class="h3 mb-1">Pengurusan Yuran</h1>
        <p class="text-muted mb-0">Jana bil bulanan dan maklumkan ahli yang masih mempunyai tunggakan.</p>
    </div>
    <a class="btn btn-outline-danger" href="{{ route('payments.index') }}">
        <i class="bi bi-wallet2 me-1" aria-hidden="true"></i> Semak Bayaran
    </a>
</div>

@unless($canManageFees)
    <div class="alert alert-info mb-4" role="status">
        <i class="bi bi-eye me-2" aria-hidden="true"></i>
        Anda mempunyai akses pemantauan sahaja. Penjanaan bil dan penghantaran peringatan dikendalikan oleh bendahari atau admin.
    </div>
@endunless

<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100"><div class="card-body">
            <span class="section-kicker">Kadar Semasa</span>
            <div class="display-6 fw-bold">RM {{ number_format($monthlyFee, 2) }}</div>
            <p class="text-muted mb-0">Yuran bagi setiap ahli aktif sebulan.</p>
        </div></div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100"><div class="card-body">
            <span class="section-kicker">Ahli Aktif</span>
            <div class="display-6 fw-bold">{{ $activeMembers->count() }}</div>
            <p class="text-muted mb-0">Ahli yang akan menerima bil bulanan.</p>
        </div></div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100"><div class="card-body">
            <span class="section-kicker">Jumlah Dibayar</span>
            <div class="display-6 fw-bold">RM {{ number_format($paidTotal, 2) }}</div>
            <p class="text-muted mb-0">Jumlah bayaran yang telah diperuntukkan.</p>
        </div></div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100"><div class="card-body">
            <span class="section-kicker">Jumlah Tunggakan</span>
            <div class="display-6 fw-bold">RM {{ number_format($outstandingTotal, 2) }}</div>
            <p class="text-muted mb-0">Daripada {{ $outstandingMembers }} ahli aktif.</p>
        </div></div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <span class="section-kicker">Pemantauan Ahli</span>
                <h2 class="h4 soft-panel-title mb-1">Status Yuran Ahli Aktif</h2>
                <p class="text-muted mb-0">Semak jumlah bil, bayaran yang telah diterima dan baki tunggakan setiap ahli.</p>
            </div>
            <span class="badge text-bg-light align-self-center">{{ $outstandingMembers }} ahli ada tunggakan</span>
        </div>
        <div class="table-responsive">
            <table class="table mobile-records align-middle mb-0">
                <thead>
                    <tr><th>Ahli</th><th>Jumlah Bil</th><th>Telah Dibayar</th><th>Tunggakan</th><th>Status</th><th>Tindakan</th></tr>
                </thead>
                <tbody>
                @forelse($activeMembers as $member)
                    <tr>
                        <td data-label="Ahli"><strong>{{ $member->name }}</strong><div class="small text-muted">{{ $member->email }}</div></td>
                        <td data-label="Jumlah Bil">RM {{ number_format($member->total_billed, 2) }}</td>
                        <td data-label="Telah Dibayar">RM {{ number_format($member->total_paid, 2) }}</td>
                        <td data-label="Tunggakan"><strong class="{{ $member->outstanding_total > 0 ? 'text-danger' : 'text-success' }}">RM {{ number_format($member->outstanding_total, 2) }}</strong></td>
                        <td data-label="Status">
                            @if($member->outstanding_total > 0)
                                <span class="badge text-bg-warning">Tertunggak</span>
                            @else
                                <span class="badge text-bg-success">Selesai</span>
                            @endif
                        </td>
                        <td data-label="Tindakan">
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-danger" href="{{ route('payments.index', ['user_id' => $member->id]) }}">Lihat Bayaran</a>
                                @if($canManageFees && $member->outstanding_total > 0)
                                    <form method="post" action="{{ route('finance.fees.reminder', $member) }}" data-confirm="Hantar peringatan tunggakan kepada {{ $member->name }}?">
                                        @csrf
                                        <button class="btn btn-sm btn-danger" type="submit"><i class="bi bi-bell me-1" aria-hidden="true"></i>Ingatkan</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Tiada ahli aktif untuk dipaparkan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canManageFees)
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <span class="section-kicker">Bil Bulanan</span>
                <h2 class="h4 soft-panel-title">Jana Yuran Ahli</h2>
                <p class="text-muted">Satu bil akan dijana untuk setiap ahli aktif. Rekod yang sudah wujud bagi bulan sama tidak akan digandakan.</p>
                <form method="post" action="{{ route('finance.fees.generate') }}" data-confirm="Jana yuran untuk bulan dipilih? Rekod sedia ada tidak akan digandakan.">
                    @csrf
                    <label class="form-label" for="billing_month">Bulan Bil</label>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <input class="form-control @error('billing_month') is-invalid @enderror" id="billing_month" type="month" name="billing_month" value="{{ old('billing_month', now()->format('Y-m')) }}">
                        <button class="btn btn-danger flex-shrink-0" type="submit"><i class="bi bi-receipt me-1" aria-hidden="true"></i> Jana Bil</button>
                    </div>
                    @error('billing_month')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <span class="section-kicker">Tindakan Susulan</span>
                <h2 class="h4 soft-panel-title">Peringatan Tunggakan</h2>
                <p class="text-muted">Hantar notifikasi dalam aplikasi dan emel kepada ahli aktif yang masih mempunyai baki yuran.</p>
                <form class="mt-auto" method="post" action="{{ route('finance.fees.reminders') }}" data-confirm="Hantar peringatan yuran kepada semua ahli yang masih tertunggak?">
                    @csrf
                    <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-bell me-1" aria-hidden="true"></i> Hantar Peringatan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@else
    <div class="card">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <span class="section-kicker">Pemantauan</span>
                <h2 class="h4 soft-panel-title mb-1">Semak Prestasi Kewangan</h2>
                <p class="text-muted mb-0">Lihat pecahan kutipan, perbelanjaan dan trend kewangan semasa.</p>
            </div>
            <a class="btn btn-outline-danger" href="{{ route('reports.financial') }}">
                <i class="bi bi-graph-up-arrow me-1" aria-hidden="true"></i> Lihat Laporan
            </a>
        </div>
    </div>
@endif
@endsection
