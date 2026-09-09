@extends('layouts.app', ['title' => 'Laporan PoliMart'])

@section('content')
<section class="admin-page">
    <div class="admin-hero">
        <div>
            <p class="stat-label mb-1">Moderation</p>
            <h1>Laporan PoliMart</h1>
            <p>Semak listing yang dilaporkan oleh komuniti staf.</p>
        </div>
        <a class="btn btn-outline-danger" href="{{ route('admin.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Kembali Admin</a>
    </div>

    <div class="card admin-panel">
        <div class="admin-report-tabs">
            <a class="{{ $status === 'pending' ? 'active' : '' }}" href="{{ route('admin.polimart.reports', ['status' => 'pending']) }}">Pending</a>
            <a class="{{ $status === 'resolved' ? 'active' : '' }}" href="{{ route('admin.polimart.reports', ['status' => 'resolved']) }}">Selesai</a>
            <a class="{{ $status === 'all' ? 'active' : '' }}" href="{{ route('admin.polimart.reports', ['status' => 'all']) }}">Semua</a>
        </div>
        <div class="table-responsive">
            <table class="table mobile-records align-middle mb-0">
                <thead><tr><th>Listing</th><th>Pelapor</th><th>Sebab</th><th>Status</th><th>Nota</th><th>Tindakan</th></tr></thead>
                <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td data-label="Listing">@if($report->item)<a href="{{ route('polimart.show', $report->item) }}">{{ $report->item->name }}</a>@else<em>Listing telah dipadam</em>@endif</td>
                        <td data-label="Pelapor">{{ $report->reporter->name }}</td>
                        <td data-label="Sebab">{{ ucfirst($report->reason) }}</td>
                        <td data-label="Status"><span class="status-badge {{ $report->status === 'pending' ? 'status-warning' : 'status-success' }}">{{ ucfirst($report->status) }}</span></td>
                        <td data-label="Nota">{{ $report->details ?: '—' }}</td>
                        <td data-label="Tindakan">
                            @if($report->status === 'pending')
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach(['dismissed' => 'Tolak', 'hidden' => 'Sembunyi', 'removed' => 'Padam'] as $reportStatus => $label)
                                        <form method="post" action="{{ route('admin.polimart.reports.update', $report) }}">
                                            @csrf
                                            @method('patch')
                                            <input type="hidden" name="status" value="{{ $reportStatus }}">
                                            <button class="btn btn-sm {{ $reportStatus === 'removed' ? 'btn-danger' : 'btn-outline-danger' }}" type="submit">{{ $label }}</button>
                                        </form>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">{{ $report->reviewer?->name ?: 'Admin' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="dashboard-empty-state my-3"><span class="stat-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span><p class="text-muted mb-0">Tiada laporan untuk tapis ini.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $reports->links() }}</div>
    </div>
</section>
@endsection
