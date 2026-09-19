@extends('layouts.public', ['title' => $activity->title])

@section('content')
<section class="public-container public-detail-hero">
    <a class="public-back-link" href="{{ route('activities.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Semua aktiviti</a>
    <p class="public-eyebrow">Aktiviti Poli</p>
    <h1>{{ $activity->title }}</h1>
    <p class="public-detail-meta"><i class="bi bi-calendar3 me-2" aria-hidden="true"></i>{{ $activity->date_time->format('d/m/Y · h:i A') }} <span>·</span> <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>{{ $activity->location }}</p>
</section>
<section class="public-container public-detail-section">
    <article class="public-detail-card">
        @if($activity->evidence_photo_path)
            <img src="{{ Storage::disk('public')->url($activity->evidence_photo_path) }}" alt="Foto {{ $activity->title }}">
        @endif
        <h2>Butiran aktiviti</h2>
        <p>{{ $activity->description ?: 'Tiada penerangan tambahan.' }}</p>
        <div class="public-detail-facts"><div><strong>Status</strong><span>{{ $activity->date_time->isPast() ? 'Telah dijalankan' : 'Akan datang' }}</span></div><div><strong>Lokasi</strong><span>{{ $activity->location }}</span></div></div>
    </article>
    @if($activity->date_time->isFuture() && $activity->registrationIsOpen())
        <aside class="public-detail-card public-activity-register-card">
            <p class="public-eyebrow">Terbuka kepada semua</p>
            <h2>Sertai aktiviti ini</h2>
            <p>Tak perlu daftar akaun. Isi maklumat ringkas di bawah dan kami akan hantar pengesahan ke emel anda.</p>
            <form method="post" action="{{ route('activities.guest-register', $activity) }}" class="public-form-grid">
                @csrf
                <label>Nama penuh
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
                </label>
                <label>Emel
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="180" autocomplete="email">
                </label>
                <label>Nombor telefon
                    <input type="tel" name="phone" value="{{ old('phone') }}" required maxlength="40" autocomplete="tel">
                </label>
                <button class="public-primary-button" type="submit">Sertai Aktiviti <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></button>
            </form>
        </aside>
    @elseif($activity->date_time->isPast())
        <aside class="public-detail-card public-activity-register-card"><p class="mb-0">Aktiviti ini telah selesai. Anda boleh melihat butiran program sebagai rujukan.</p></aside>
    @endif
</section>
@endsection
