@extends('layouts.app', ['title' => \App\Support\PolistaffLabels::status($status)])

@section('content')
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
                    @if($status === 'approved')<th>Tindakan</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td data-label="Aktiviti">{{ $activity->title }}</td>
                        <td data-label="Tarikh">{{ $activity->date_time->format('d/m/Y h:i A') }}</td>
                        <td data-label="Lokasi">{{ $activity->location }}</td>
                        <td data-label="Status"><span class="badge {{ \App\Support\PolistaffLabels::statusClass($activity->status) }}">{{ \App\Support\PolistaffLabels::status($activity->status) }}</span></td>
                        @if($status === 'approved')
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
                    <tr><td colspan="{{ $status === 'approved' ? 5 : 4 }}" class="text-muted">Tiada aktiviti dalam senarai ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $activities->links() }}</div>
@endsection
