@extends('layouts.public', ['title' => 'Aktiviti Poli'])

@section('content')
<section class="public-container public-listing-hero">
    <p class="public-eyebrow">Program komuniti Poli</p>
    <h1>{{ $view === 'past' ? 'Aktiviti yang telah dijalankan' : 'Aktiviti akan datang' }}</h1>
    <p>{{ $view === 'past' ? 'Lihat rekod program dan aktiviti yang pernah dianjurkan oleh pihak Poli.' : 'Ketahui program yang akan datang dan sertai aktiviti yang terbuka kepada komuniti.' }}</p>
</section>

<section class="public-container public-activity-grid-section">
    <div class="public-activity-grid">
        @forelse($activities as $activity)
            <article class="public-activity-card">
                <div class="public-activity-date"><strong>{{ $activity->date_time->format('d') }}</strong><span>{{ mb_strtoupper($activity->date_time->format('M')) }}</span></div>
                <div>
                    <p class="public-activity-meta">{{ $activity->date_time->format('d/m/Y · h:i A') }} · {{ $activity->location }}</p>
                    <h2>{{ $activity->title }}</h2>
                    <a class="btn btn-outline-primary" href="{{ route('activities.show', $activity) }}">Lihat aktiviti <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                </div>
            </article>
        @empty
            <div class="public-empty-state"><i class="bi bi-calendar3" aria-hidden="true"></i><h2>Tiada aktiviti buat masa ini.</h2><p>Sila kembali semula untuk kemas kini program.</p></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $activities->links() }}</div>
</section>
@endsection
