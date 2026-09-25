@extends('layouts.app', ['title' => 'Aktiviti'])

@section('content')
@php
    $user = auth()->user();
    $showDraftCard = $user->hasRole('member');
    $summaryColumn = $showDraftCard ? 'col-md-3' : 'col-md-4';
    $pendingCardStatus = $user->hasRole('treasurer') ? 'pending_approval' : ($user->hasRole('admin') ? 'treasurer_verified' : 'pending_approval');
    $pendingCardLabel = $user->hasRole('treasurer')
        ? 'Jumlah Permohonan Aktiviti Baru'
        : ($user->hasRole('admin') ? 'Menunggu Kelulusan Admin' : 'Menunggu Semakan');
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Aktiviti</h1>
    @can('manage-activities')<a class="btn btn-danger" href="{{ route('activities.create') }}"><i class="bi bi-plus-lg me-1"></i>Cipta Aktiviti</a>@endcan
</div>

<div class="row g-3 mb-4">
    @foreach([['approved', 'Aktiviti Diluluskan', 'bi-check-circle'], ['rejected', 'Aktiviti Ditolak', 'bi-x-circle'], [$pendingCardStatus, $pendingCardLabel, 'bi-hourglass-split']] as [$key, $label, $icon])
        <div class="{{ $summaryColumn }}"><a class="admin-stat-card activity-summary-card" href="{{ route('activities.status-list', ['status' => $key]) }}"><span class="admin-stat-icon"><i class="bi {{ $icon }}"></i></span><strong>{{ $stats[$key === 'pending_approval' ? 'pending' : $key] }}</strong><span>{{ $label }}</span><small class="text-muted">Klik untuk lihat senarai</small></a></div>
    @endforeach
    @if($showDraftCard)
        <div class="{{ $summaryColumn }}"><a class="admin-stat-card activity-summary-card" href="{{ route('activities.status-list', ['status' => 'draft']) }}"><span class="admin-stat-icon"><i class="bi bi-pencil-square"></i></span><strong>{{ $stats['draft'] }}</strong><span>Draf Aktiviti</span><small class="text-muted">Sambung atau urus draf</small></a></div>
    @endif
</div>

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
@endsection
