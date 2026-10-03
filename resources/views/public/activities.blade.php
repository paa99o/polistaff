@extends('layouts.public', ['title' => 'Aktiviti Poli'])

@section('content')
<section class="public-container public-listing-hero">
    <p class="public-eyebrow">Program komuniti Poli</p>
    <h1>{{ ['past' => 'Aktiviti yang telah dijalankan', 'upcoming' => 'Aktiviti akan datang', 'all' => 'Semua aktiviti'][$view] }}</h1>
    <p>{{ $view === 'past' ? 'Lihat rekod program dan aktiviti yang pernah dianjurkan oleh pihak Poli.' : ($view === 'upcoming' ? 'Ketahui program yang akan datang dan sertai aktiviti yang terbuka kepada komuniti.' : 'Terokai program yang akan datang dan aktiviti yang pernah dianjurkan oleh pihak Poli.') }}</p>
</section>

<section class="public-container public-activity-grid-section">
    <nav class="public-activity-filters" aria-label="Tapis aktiviti">
        <a class="{{ $view === 'all' ? 'is-active' : '' }}" href="{{ route('activities.index') }}"><i class="bi bi-grid me-1" aria-hidden="true"></i>Semua aktiviti</a>
        <a class="{{ $view === 'upcoming' ? 'is-active' : '' }}" href="{{ route('activities.index', ['view' => 'upcoming']) }}"><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>Akan datang</a>
        <a class="{{ $view === 'past' ? 'is-active' : '' }}" href="{{ route('activities.index', ['view' => 'past']) }}"><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>Telah dijalankan</a>
    </nav>
    <div class="public-activity-grid">
        @forelse($activities as $activity)
            <article class="public-activity-card">
                <div class="public-activity-date {{ $activity->date_time->isPast() ? 'is-past' : '' }}"><strong>{{ $activity->date_time->format('d') }}</strong><span>{{ mb_strtoupper($activity->date_time->format('M')) }}</span></div>
                <div>
                    <p class="public-activity-meta">{{ $activity->date_time->format('d/m/Y · h:i A') }} · {{ $activity->location }}</p>
                    <h2>{{ $activity->title }}</h2>
                    <span class="public-activity-list-status {{ $activity->date_time->isPast() ? 'is-past' : '' }}"><i class="bi {{ $activity->date_time->isPast() ? 'bi-check-circle' : 'bi-calendar-event' }} me-1" aria-hidden="true"></i>{{ $activity->date_time->isPast() ? 'Telah dijalankan' : 'Akan datang' }}</span>
                    <a class="btn btn-outline-primary" href="{{ route('activities.show', $activity) }}">Lihat aktiviti <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                </div>
            </article>
        @empty
            <div class="public-empty-state"><i class="bi bi-calendar3" aria-hidden="true"></i><h2>{{ $view === 'past' ? 'Belum ada aktiviti lepas.' : ($view === 'upcoming' ? 'Belum ada aktiviti akan datang.' : 'Belum ada aktiviti untuk dipaparkan.') }}</h2><p>Sila kembali semula untuk kemas kini program.</p></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $activities->links() }}</div>
</section>
@endsection
