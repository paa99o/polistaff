@extends('layouts.app', ['title' => 'Aktiviti'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Aktiviti</h1>
    @can('manage-activities')<a class="btn btn-danger" href="{{ route('activities.create') }}"><i class="bi bi-plus-lg me-1"></i>Cipta Aktiviti</a>@endcan
</div>

<div class="row g-3 mb-4">
    @foreach([['approved', 'Aktiviti Diluluskan', 'bi-check-circle'], ['rejected', 'Aktiviti Ditolak', 'bi-x-circle'], ['pending_approval', 'Menunggu Semakan', 'bi-hourglass-split'], ['treasurer_verified', 'Disahkan Bendahari', 'bi-person-check']] as [$key, $label, $icon])
        <div class="col-md-3"><a class="admin-stat-card activity-summary-card" href="{{ route('activities.index', ['status' => $key]) }}"><span class="admin-stat-icon"><i class="bi {{ $icon }}"></i></span><strong>{{ $stats[$key === 'pending_approval' ? 'pending' : $key] }}</strong><span>{{ $label }}</span><small class="text-muted">Klik untuk lihat senarai</small></a></div>
    @endforeach
</div>

@if(request()->filled('status'))
    <div class="alert alert-info d-flex justify-content-between align-items-center"><span>Menapis: <strong>{{ \App\Support\PolistaffLabels::status(request('status')) }}</strong></span><a href="{{ route('activities.index') }}" class="btn btn-sm btn-outline-secondary">Buang Tapis</a></div>
@endif

<section class="card activity-calendar-panel mb-4">
    <div class="card-body">
        <div class="activity-calendar-header">
            <div>
                <p class="stat-label mb-1">Jadual program</p>
                <h2 class="h4 mb-0">{{ $calendarMonth->translatedFormat('F Y') }}</h2>
            </div>
            <div class="activity-calendar-nav">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('activities.index', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}" aria-label="Bulan sebelumnya" title="Bulan sebelumnya"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                <a class="btn btn-sm btn-outline-danger" href="{{ route('activities.index', ['month' => now()->format('Y-m')]) }}">Bulan ini</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('activities.index', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}" aria-label="Bulan seterusnya" title="Bulan seterusnya"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </div>
        </div>

        <div class="activity-calendar" role="grid" aria-label="Kalendar aktiviti {{ $calendarMonth->translatedFormat('F Y') }}">
            @foreach(['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'] as $weekday)
                <div class="activity-calendar-weekday" role="columnheader">{{ $weekday }}</div>
            @endforeach
            @foreach($calendarWeeks as $week)
                @foreach($week as $day)
                    <div class="activity-calendar-day {{ $day['date']->month !== $calendarMonth->month ? 'is-outside' : '' }} {{ $day['date']->isToday() ? 'is-today' : '' }}" role="gridcell">
                        <span class="activity-calendar-date">{{ $day['date']->day }}</span>
                        <div class="activity-calendar-events">
                            @foreach($day['activities'] as $activity)
                                <a class="activity-calendar-event activity-status-{{ $activity->status }}" href="{{ route('activities.show', $activity) }}" title="{{ $activity->title }}">
                                    <span>{{ $activity->date_time->format('h:i A') }}</span> {{ $activity->title }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</section>

<form class="row g-2 mb-3">
    <div class="col-auto">
        <label class="visually-hidden" for="activity-date">Tarikh aktiviti</label>
        <input class="form-control" id="activity-date" type="date" name="date" value="{{ request('date') }}">
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-secondary">Tapis</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table mobile-records mb-0">
            <thead>
                <tr><th>Tajuk</th><th>Tarikh</th><th>Lokasi</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td data-label="Aktiviti">{{ $activity->title }}</td>
                        <td data-label="Tarikh">{{ $activity->date_time->format('d/m/Y h:i A') }}</td>
                        <td data-label="Lokasi">{{ $activity->location }}</td>
                        <td data-label="Status">
                            <span class="badge {{ \App\Support\PolistaffLabels::statusClass($activity->status) }}">{{ \App\Support\PolistaffLabels::status($activity->status) }}</span>
                        </td>
                        <td data-label="Tindakan">
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-sm btn-outline-danger" href="{{ route('activities.show', $activity) }}">Lihat</a>
                                @if(auth()->id() === $activity->created_by && $activity->status === 'pending_approval')
                                    <a class="btn btn-sm btn-danger" href="{{ route('activities.edit', $activity) }}">Ubah</a>
                                @endif
                                @if($activity->status === 'approved' && $activity->isFinished())
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.activities.pdf', $activity) }}">Download Report</a>
                                    @if($activity->created_by === auth()->id() || auth()->user()->hasRole('treasurer','admin'))
                                        <form method="post" action="{{ route('activities.report-photo', $activity) }}" enctype="multipart/form-data" class="d-flex gap-1 align-items-center">
                                            @csrf
                                            <label class="btn btn-sm btn-outline-secondary mb-0">Gambar<input class="d-none" type="file" name="report_photo" accept="image/*" required onchange="this.form.submit()"></label>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Tiada aktiviti.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $activities->links() }}</div>
@endsection
