@extends('layouts.app', ['title' => 'Sumbangan'])

@section('content')
@php
    $user = auth()->user();
    $isTreasurer = $user->hasRole('treasurer');
    $isFinanceReviewer = $user->hasRole('treasurer', 'admin');
    $canApproveDonations = $user->hasRole('admin');
    $labels = ['pending' => 'Menunggu semakan bendahari', 'treasurer_verified' => 'Menunggu kelulusan admin', 'approved' => 'Diluluskan', 'rejected' => 'Ditolak'];
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><p class="dashboard-kicker mb-1">Kewangan kelab</p><h1 class="h3 mb-0">Sumbangan</h1><p class="text-muted mb-0">Mohon bantuan sumbangan dan semak status kelulusan.</p></div>
    <a class="btn btn-danger" href="{{ route('donations.create') }}"><i class="bi bi-heart me-2" aria-hidden="true"></i>Mohon Sumbangan</a>
</div>
<div class="card"><div class="table-responsive"><table class="table mobile-records align-middle mb-0"><thead><tr><th>Jenis Sumbangan</th><th>Ahli</th><th>Had Bendahari</th><th>Amaun Diluluskan</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
@forelse($donations as $donation)<tr><td data-label="Jenis"><strong>{{ $donation->category }}</strong><div class="small text-muted">{{ $donation->request_date->format('d/m/Y') }}</div></td><td data-label="Ahli">{{ $donation->user->name }}</td><td data-label="Had">{{ $donation->limit_amount !== null ? 'RM '.number_format((float) $donation->limit_amount, 2) : '-' }}</td><td data-label="Amaun">{{ $donation->amount !== null ? 'RM '.number_format((float) $donation->amount, 2) : '-' }}</td><td data-label="Status"><span class="badge bg-secondary">{{ $labels[$donation->status] ?? $donation->status }}</span></td><td data-label="Tindakan"><div class="d-flex flex-wrap gap-2">
    @if($isTreasurer && $donation->status === 'pending')<button class="btn btn-sm btn-outline-danger" type="button" data-donation-action data-bs-toggle="modal" data-bs-target="#donationActionModal" data-action="{{ route('donations.verify', $donation) }}" data-kind="support">Sokong</button>@endif
    @if($canApproveDonations && $donation->status === 'treasurer_verified')<button class="btn btn-sm btn-danger" type="button" data-donation-action data-bs-toggle="modal" data-bs-target="#donationActionModal" data-action="{{ route('donations.approve', $donation) }}" data-kind="approve" data-limit="{{ $donation->limit_amount }}">Luluskan</button>@endif
    @if($isFinanceReviewer && in_array($donation->status, ['pending', 'treasurer_verified'], true))<button class="btn btn-sm btn-outline-secondary" type="button" data-donation-action data-bs-toggle="modal" data-bs-target="#donationActionModal" data-action="{{ route('donations.reject', $donation) }}" data-kind="reject">Tolak</button>@endif
    <a class="btn btn-sm btn-outline-danger" href="{{ route('donations.show', $donation) }}">Keterangan</a>
</div></td></tr>@empty<tr><td colspan="6"><div class="dashboard-empty-state my-3"><span class="stat-icon"><i class="bi bi-heart" aria-hidden="true"></i></span><p class="text-muted mb-0">Tiada permohonan sumbangan.</p></div></td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $donations->links() }}</div>

@if($isFinanceReviewer)
<div class="modal fade" id="donationActionModal" tabindex="-1" aria-labelledby="donationActionTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form id="donationActionForm" method="post">@csrf @method('patch')<div class="modal-header"><h2 class="modal-title h5" id="donationActionTitle">Tindakan sumbangan</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><p id="donationActionHelp" class="text-muted"></p>
    <div id="donationLimitField" class="mb-3"><label class="form-label" for="donationActionLimit">Had Sumbangan</label><input class="form-control" id="donationActionLimit" type="number" name="limit_amount" min="0.01" step="0.01"></div>
    <div id="donationAmountField" class="mb-3"><label class="form-label" for="donationActionAmount">Amaun Sumbangan</label><input class="form-control" id="donationActionAmount" type="number" name="amount" min="0.01" step="0.01"></div>
    <div id="donationNotesField"><label class="form-label" id="donationNotesLabel" for="donationActionNotes">Catatan</label><textarea class="form-control" id="donationActionNotes" rows="3"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger" id="donationActionSubmit" type="submit">Teruskan</button></div></form></div></div></div>
@push('scripts')
<script>
    document.querySelectorAll('[data-donation-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const kind = button.dataset.kind;
            const form = document.getElementById('donationActionForm');
            const limit = document.getElementById('donationActionLimit');
            const amount = document.getElementById('donationActionAmount');
            const notes = document.getElementById('donationActionNotes');
            const title = { support: 'Sokong sumbangan', approve: 'Luluskan sumbangan', reject: 'Tolak sumbangan' }[kind];
            form.action = button.dataset.action;
            document.getElementById('donationActionTitle').textContent = title;
            document.getElementById('donationActionSubmit').textContent = title;
            document.getElementById('donationActionHelp').textContent = kind === 'support'
                ? 'Tetapkan had maksimum sumbangan berdasarkan baki kelab.'
                : kind === 'approve' ? `Amaun tidak boleh melebihi had bendahari RM ${Number(button.dataset.limit).toFixed(2)}.` : 'Nyatakan sebab penolakan permohonan.';
            document.getElementById('donationLimitField').hidden = kind !== 'support';
            document.getElementById('donationAmountField').hidden = kind !== 'approve';
            limit.required = kind === 'support';
            amount.required = kind === 'approve';
            amount.max = button.dataset.limit || '';
            limit.value = '';
            amount.value = '';
            notes.name = kind === 'support' ? 'treasurer_notes' : 'review_notes';
            notes.required = kind === 'reject';
            notes.value = '';
            document.getElementById('donationNotesLabel').textContent = kind === 'reject' ? 'Sebab ditolak' : 'Catatan (pilihan)';
        });
    });
</script>
@endpush
@endif
@endsection
