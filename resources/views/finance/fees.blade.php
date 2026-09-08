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
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <span class="section-kicker">Kadar Semasa</span>
            <div class="display-6 fw-bold">RM {{ number_format($monthlyFee, 2) }}</div>
            <p class="text-muted mb-0">Yuran bagi setiap ahli aktif sebulan.</p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <span class="section-kicker">Ahli Aktif</span>
            <div class="display-6 fw-bold">{{ $activeMembers }}</div>
            <p class="text-muted mb-0">Ahli yang akan menerima bil bulanan.</p>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <span class="section-kicker">Jumlah Tunggakan</span>
            <div class="display-6 fw-bold">RM {{ number_format($outstandingTotal, 2) }}</div>
            <p class="text-muted mb-0">Daripada {{ $outstandingMembers }} ahli aktif.</p>
        </div></div>
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
