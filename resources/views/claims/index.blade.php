@extends('layouts.app', ['title' => 'Tuntutan Perbelanjaan'])

@section('content')
@php
    $statusLabels = [
        'pending' => 'Menunggu semakan',
        'treasurer_verified' => 'Disahkan bendahari',
        'approved' => 'Diluluskan',
        'rejected' => 'Ditolak',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <p class="dashboard-kicker mb-1">Kewangan kelab</p>
        <h1 class="h3 mb-0">Tuntutan Perbelanjaan</h1>
        <p class="text-muted mb-0">Hantar tuntutan dan semak status kelulusan.</p>
    </div>
    <a class="btn btn-danger" href="{{ route('claims.create') }}">
        <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Tuntutan Baru
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach($statusLabels as $status => $label)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="category">Kategori</label>
                <input class="form-control" id="category" name="category" value="{{ request('category') }}" placeholder="Contoh: Makanan">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="from">Dari</label>
                <input class="form-control" id="from" type="date" name="from" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="to">Hingga</label>
                <input class="form-control" id="to" type="date" name="to" value="{{ request('to') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-danger w-100">Tapis</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mobile-records align-middle mb-0">
            <thead>
                <tr>
                    <th>Tuntutan</th>
                    <th>Ahli</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Disahkan Oleh</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $claim)
                    <tr>
                        <td data-label="Tuntutan">
                            <strong>{{ $claim->title }}</strong>
                            <div class="small text-muted">{{ $claim->category }} &middot; {{ $claim->claim_date->format('d/m/Y') }}</div>
                        </td>
                        <td data-label="Ahli">{{ $claim->user->name }}</td>
                        <td data-label="Jumlah">RM {{ number_format((float) $claim->amount, 2) }}</td>
                        <td data-label="Status"><span class="badge bg-secondary">{{ $statusLabels[$claim->status] ?? $claim->status }}</span></td>
                        <td data-label="Disahkan Oleh">{{ $claim->treasurerVerifier->name ?? '-' }}</td>
                        <td data-label="Tindakan"><a class="btn btn-sm btn-outline-danger" href="{{ route('claims.show', $claim) }}">Semak</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="dashboard-empty-state my-3">
                                <span class="stat-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                                <p class="text-muted mb-0">Tiada tuntutan untuk tapisan ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $claims->links() }}</div>
@endsection
