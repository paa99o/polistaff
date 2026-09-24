@extends('layouts.app', ['title' => 'Tuntutan Perbelanjaan'])

@section('content')
@php
    $statusLabels = [
        'pending' => 'Menunggu semakan',
        'treasurer_verified' => 'Disahkan bendahari',
        'approved' => 'Diluluskan',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <p class="dashboard-kicker mb-1">Kewangan kelab</p>
        <h1 class="h3 mb-0">Tuntutan Perbelanjaan</h1>
        <p class="text-muted mb-0">Hantar tuntutan dan semak status kelulusan.</p>
    </div>
    <form class="d-flex flex-wrap align-items-center gap-2" method="get" action="{{ route('claims.create') }}">
        <select class="form-select" name="category" aria-label="Pilih jenis tuntutan" required>
            <option value="">Pilih jenis tuntutan</option>
            <option value="Khairat Kematian">Khairat Kematian</option>
            <option value="Sambutan Harijadi Staff">Sambutan Harijadi Staff</option>
            <option value="Hadiah Kejayaan Anak">Hadiah Kejayaan Anak</option>
        </select>
        <span class="claim-fixed-amount">RM 100.00</span>
        <button class="btn btn-danger" type="submit"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Buat Tuntutan</button>
    </form>
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
                                <p class="text-muted mb-0">Tiada tuntutan untuk dipaparkan.</p>
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
