@extends('layouts.app', ['title' => 'Tuntutan Perbelanjaan'])

@section('content')
@php
    $user = auth()->user();
    $isTreasurer = $user->hasRole('treasurer');
    $isFinanceReviewer = $user->hasRole('treasurer', 'admin');
    $canApproveClaims = $user->hasRole('admin');
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
                    <th>Tindakan</th>
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
                        <td data-label="Tindakan">
                            <div class="d-flex flex-wrap gap-2">
                                @if($isTreasurer && $claim->status === 'pending')
                                    <button class="btn btn-sm btn-outline-danger" type="button" data-review-action data-bs-toggle="modal" data-bs-target="#claimActionModal" data-action="{{ route('claims.verify', $claim) }}" data-kind="support">Sokong</button>
                                @endif
                                @if($canApproveClaims && $claim->status === 'treasurer_verified')
                                    <button class="btn btn-sm btn-danger" type="button" data-review-action data-bs-toggle="modal" data-bs-target="#claimActionModal" data-action="{{ route('claims.approve', $claim) }}" data-kind="approve">Luluskan</button>
                                @endif
                                @if($isFinanceReviewer && in_array($claim->status, ['pending', 'treasurer_verified'], true))
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-review-action data-bs-toggle="modal" data-bs-target="#claimActionModal" data-action="{{ route('claims.reject', $claim) }}" data-kind="reject">Tolak</button>
                                @endif
                                <a class="btn btn-sm btn-outline-danger" href="{{ route('claims.show', $claim) }}">Keterangan</a>
                            </div>
                        </td>
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

@if($isFinanceReviewer)
    <div class="modal fade" id="claimActionModal" tabindex="-1" aria-labelledby="claimActionTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="claimActionForm" method="post">
                    @csrf
                    @method('patch')
                    <div class="modal-header"><h2 class="modal-title h5" id="claimActionTitle">Tindakan tuntutan</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <p id="claimActionHelp" class="text-muted"></p>
                        <div id="claimNotesField">
                            <label class="form-label" id="claimNotesLabel" for="claimActionNotes">Catatan</label>
                            <textarea class="form-control" id="claimActionNotes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger" id="claimActionSubmit" type="submit">Teruskan</button></div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.querySelectorAll('[data-review-action][data-bs-target="#claimActionModal"]').forEach((button) => {
                button.addEventListener('click', () => {
                    const kind = button.dataset.kind;
                    const form = document.getElementById('claimActionForm');
                    const notes = document.getElementById('claimActionNotes');
                    const title = { support: 'Sokong tuntutan', approve: 'Luluskan tuntutan', reject: 'Tolak tuntutan' }[kind];
                    form.action = button.dataset.action;
                    document.getElementById('claimActionTitle').textContent = title;
                    document.getElementById('claimActionSubmit').textContent = title;
                    document.getElementById('claimActionHelp').textContent = kind === 'support'
                        ? 'Tuntutan ini akan dihantar kepada admin untuk kelulusan akhir.'
                        : kind === 'approve' ? 'Tuntutan akan diluluskan dan transaksi perbelanjaan dijana.' : 'Nyatakan sebab penolakan tuntutan.';
                    document.getElementById('claimNotesLabel').textContent = kind === 'reject' ? 'Sebab ditolak' : 'Catatan (pilihan)';
                    notes.name = kind === 'support' ? 'treasurer_notes' : 'review_notes';
                    notes.required = kind === 'reject';
                    notes.value = '';
                });
            });
        </script>
    @endpush
@endif
@endsection
