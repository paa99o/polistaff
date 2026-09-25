@extends('layouts.app', ['title' => \App\Support\PolistaffLabels::status($status)])

@section('content')
@php
    $canReviewNewRequests = auth()->user()->hasRole('treasurer') && $status === 'pending_approval';
    $canReviewVerifiedRequests = auth()->user()->hasRole('admin') && $status === 'treasurer_verified';
    $hasInlineReviewActions = $canReviewNewRequests || $canReviewVerifiedRequests;
    $isDraftList = $status === 'draft';
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <p class="stat-label mb-1">Senarai aktiviti</p>
        <h1 class="h3 mb-0">{{ \App\Support\PolistaffLabels::status($status) }}</h1>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('activities.index') }}"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali ke Aktiviti</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mobile-records mb-0">
            <thead>
                <tr>
                    <th>Tajuk</th>
                    <th>Tarikh</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    @if($status === 'approved' || $hasInlineReviewActions || $isDraftList || in_array($status, ['pending_approval', 'treasurer_verified'], true))<th>Tindakan</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td data-label="Aktiviti">{{ $activity->title }}</td>
                        <td data-label="Tarikh">{{ $activity->date_time->format('d/m/Y h:i A') }}</td>
                        <td data-label="Lokasi">{{ $activity->location }}</td>
                        <td data-label="Status"><span class="badge {{ \App\Support\PolistaffLabels::statusClass($activity->status) }}">{{ \App\Support\PolistaffLabels::status($activity->status) }}</span></td>
                        @if($isDraftList)
                            <td data-label="Tindakan"><div class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-danger" href="{{ route('activities.edit', $activity) }}">Sambung Draf</a><form method="post" action="{{ route('activities.destroy', $activity) }}" data-confirm="Padam draf aktiviti ini?">@csrf @method('delete')<button class="btn btn-sm btn-outline-secondary" type="submit">Padam</button></form></div></td>
                        @elseif($hasInlineReviewActions)
                            <td data-label="Tindakan">
                                <div class="d-flex gap-2 flex-wrap">
                                    @if($canReviewNewRequests)
                                        <form method="post" action="{{ route('activities.verify', $activity) }}" data-confirm="Sokong permohonan aktiviti ini dan hantar kepada admin?">
                                            @csrf
                                            @method('patch')
                                            <input type="hidden" name="treasurer_notes" value="Aktiviti disokong bendahari.">
                                            <button class="btn btn-sm btn-danger" type="submit">Luluskan</button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('activities.approve', $activity) }}" data-confirm="Luluskan aktiviti ini dan buka kepada ahli?">
                                            @csrf
                                            @method('patch')
                                            <button class="btn btn-sm btn-danger" type="submit">Luluskan</button>
                                        </form>
                                    @endif
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#rejectActivityModal" data-action="{{ route('activities.reject', $activity) }}">Tolak</button>
                                    <a class="btn btn-sm btn-outline-danger" href="{{ route('activities.show', $activity) }}">Keterangan</a>
                                    <form method="post" action="{{ route('activities.paperwork.generate', $activity) }}">@csrf<button class="btn btn-sm btn-outline-secondary" type="submit">Jana Kertas Kerja</button></form>
                                </div>
                            </td>
                        @elseif(in_array($status, ['pending_approval', 'treasurer_verified'], true))
                            <td data-label="Tindakan"><div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-sm btn-outline-danger" href="{{ route('activities.show', $activity) }}">Keterangan</a>
                                @if($activity->paperworkVersions->isNotEmpty())
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('activities.paperwork.preview', [$activity, $activity->paperworkVersions->first()]) }}">Pratonton Kertas Kerja</a>
                                @else
                                    <form method="post" action="{{ route('activities.paperwork.generate', $activity) }}">@csrf<button class="btn btn-sm btn-outline-secondary" type="submit">Jana Kertas Kerja</button></form>
                                @endif
                            </div></td>
                        @elseif($status === 'approved')
                            <td data-label="Tindakan">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-sm btn-outline-danger" href="{{ route('activities.show', $activity) }}">Lihat Butiran</a>
                                    @if($activity->isFinished())
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.activities.pdf', $activity) }}">Muat Turun Kertas Kerja</a>
                                    @else
                                        <span class="small text-muted align-self-center">Kertas kerja tersedia selepas aktiviti tamat.</span>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $status === 'approved' || $hasInlineReviewActions || $isDraftList || in_array($status, ['pending_approval', 'treasurer_verified'], true) ? 5 : 4 }}" class="text-muted">Tiada aktiviti dalam senarai ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $activities->links() }}</div>

@if($hasInlineReviewActions)
    <div class="modal fade" id="rejectActivityModal" tabindex="-1" aria-labelledby="rejectActivityTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="rejectActivityForm" method="post">
                    @csrf
                    @method('patch')
                    <div class="modal-header"><h2 class="modal-title h5" id="rejectActivityTitle">Tolak permohonan aktiviti</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body"><label class="form-label" for="activityRejectionReason">Sebab ditolak</label><textarea class="form-control" id="activityRejectionReason" name="review_notes" rows="3" required></textarea></div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger" type="submit">Tolak Permohonan</button></div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.querySelectorAll('[data-bs-target="#rejectActivityModal"]').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById('rejectActivityForm').action = button.dataset.action;
                    document.getElementById('activityRejectionReason').value = '';
                });
            });
        </script>
    @endpush
@endif
@endsection
