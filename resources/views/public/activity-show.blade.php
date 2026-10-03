@extends('layouts.public', ['title' => $activity->title])

@section('content')
@php
    $proposal = $activity->proposal_data ?? [];
    $objectives = collect($proposal['objectives'] ?? [])->filter(fn ($item) => filled($item));
    $participants = collect($proposal['target_participants'] ?? [])->filter(fn ($item) => filled($item));
    $tentative = collect($proposal['tentative'] ?? [])->filter(fn ($row) => filled($row['description'] ?? null));
@endphp
<section class="public-container public-detail-hero">
    <a class="public-back-link" href="{{ route('activities.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Semua aktiviti</a>
    <p class="public-eyebrow">Aktiviti Poli</p>
    <h1>{{ $activity->title }}</h1>
    <p class="public-detail-meta"><i class="bi bi-calendar3 me-2" aria-hidden="true"></i>{{ $activity->date_time->format('d/m/Y · h:i A') }}@if($activity->end_time)<span>·</span><i class="bi bi-clock me-2" aria-hidden="true"></i>{{ $activity->end_time->format('d/m/Y · h:i A') }}@endif <span>·</span> <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>{{ $activity->location }}</p>
</section>
<section class="public-container public-detail-section">
    <article class="public-detail-card">
        <div class="public-detail-heading">
            <div><p class="public-eyebrow mb-2">Kenali program ini</p><h2>Butiran aktiviti</h2></div>
            <span class="public-detail-status"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ $activity->date_time->isPast() ? 'Telah dijalankan' : 'Akan datang' }}</span>
        </div>
        @if($activity->evidencePhotos->isNotEmpty())
            <div class="public-detail-gallery">
                @foreach($activity->evidencePhotos as $photo)
                    <img src="{{ asset('storage/'.$photo->path) }}" alt="Gambar {{ $activity->title }}">
                @endforeach
            </div>
        @endif
        @if(filled($proposal['summary'] ?? null) || filled($activity->description))
            <section class="public-detail-copy">
                <h3><i class="bi bi-card-text" aria-hidden="true"></i> Tentang program</h3>
                <p>{{ $proposal['summary'] ?? $activity->description }}</p>
            </section>
        @endif
        <div class="public-detail-facts">
            <div><i class="bi bi-geo-alt" aria-hidden="true"></i><strong>Lokasi</strong><span>{{ $activity->location }}</span></div>
            @if(filled($activity->organizing_unit))<div><i class="bi bi-building" aria-hidden="true"></i><strong>Penganjur</strong><span>{{ $activity->organizing_unit }}</span></div>@endif
            @if(filled($activity->activity_type) || filled($activity->program_category))<div><i class="bi bi-bookmark" aria-hidden="true"></i><strong>Kategori</strong><span>{{ collect([$activity->activity_type, $activity->program_category])->filter()->unique()->implode(' · ') }}</span></div>@endif
            @if(filled($proposal['program_level'] ?? null))<div><i class="bi bi-people" aria-hidden="true"></i><strong>Peringkat program</strong><span>{{ $proposal['program_level'] }}</span></div>@endif
            @if($activity->expected_participants)<div><i class="bi bi-person-plus" aria-hidden="true"></i><strong>Sasaran kehadiran</strong><span>{{ number_format($activity->expected_participants) }} peserta</span></div>@endif
        </div>
        @if($participants->isNotEmpty())
            <section class="public-detail-copy">
                <h3><i class="bi bi-person-hearts" aria-hidden="true"></i> Untuk siapa?</h3>
                <div class="public-detail-tags">@foreach($participants as $participant)<span>{{ $participant }}</span>@endforeach</div>
            </section>
        @endif
        @if($objectives->isNotEmpty())
            <section class="public-detail-copy">
                <h3><i class="bi bi-bullseye" aria-hidden="true"></i> Apa yang ingin dicapai</h3>
                <ul class="public-detail-objectives">@foreach($objectives as $objective)<li>{{ $objective }}</li>@endforeach</ul>
            </section>
        @endif
        @if(filled($proposal['impact'] ?? null))
            <section class="public-detail-impact"><i class="bi bi-stars" aria-hidden="true"></i><div><strong>Manfaat program</strong><p>{{ $proposal['impact'] }}</p></div></section>
        @endif
        @if($tentative->isNotEmpty())
            <section class="public-detail-copy public-detail-schedule">
                <h3><i class="bi bi-list-check" aria-hidden="true"></i> Tentatif program</h3>
                <div class="public-detail-timeline">
                    @foreach($tentative as $row)
                        <div><span>{{ filled($row['time'] ?? null) ? \Carbon\Carbon::parse($row['time'])->format('h:i A') : 'Program' }}</span><p>{{ $row['description'] }}</p>@if(filled($row['date'] ?? null))<small>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</small>@endif</div>
                    @endforeach
                </div>
            </section>
        @endif
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
        <aside class="public-detail-card public-activity-register-card public-activity-finished"><i class="bi bi-calendar-check" aria-hidden="true"></i><div><p class="public-eyebrow">Rekod program</p><h2>Aktiviti ini telah selesai</h2><p class="mb-0">Semak maklumat dan gambar program di atas sebagai rujukan komuniti.</p></div></aside>
    @endif
</section>
@endsection
