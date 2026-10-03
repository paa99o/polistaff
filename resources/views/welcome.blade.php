<!doctype html>
<html lang="ms"
    data-theme-preference="{{ auth()->check() ? auth()->user()->theme_preference : 'light' }}"
    data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
    data-text-size="{{ auth()->check() ? auth()->user()->text_size_preference : 'normal' }}"
    @if(auth()->check() && auth()->user()->reduce_motion) data-reduce-motion="true" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aktiviti, komuniti dan urusan Kelab Staf Politeknik Besut dalam satu tempat.">
    <title>POLIBEST · Portal Pengurusan Kelab Staf</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <meta name="theme-color" content="#4F8074">
    @include('partials.theme-loader')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="public-shell homepage-shell">
    <header class="public-nav">
        <div class="public-container d-flex align-items-center justify-content-between w-100">
            <a class="public-brand" href="{{ url('/') }}">
                @include('partials.brand-mark')
                <span><span class="brand-name">POLIBEST</span><span class="brand-description text-muted">Portal Pengurusan Kelab Staf</span></span>
            </a>
            <nav class="public-links" aria-label="Navigasi utama">
                <a href="{{ route('activities.index') }}">Aktiviti</a>
                <a href="{{ route('polimart.index') }}"><i class="bi bi-bag me-1" aria-hidden="true"></i>PoliMart</a>
                @auth<a class="public-nav-button" href="{{ route('dashboard') }}">Dashboard</a>@else<a class="public-nav-button" href="{{ route('login') }}">Log Masuk</a>@endauth
            </nav>
            @include('partials.theme-switcher')
        </div>
    </header>

    <main>
        <section class="homepage-hero" aria-labelledby="homepage-title">
            <svg class="homepage-wave-pattern" viewBox="0 0 1440 180" preserveAspectRatio="none" fill="none" aria-hidden="true">
                <path d="M0 94C178 54 286 50 438 88s244 65 399 20 264-73 385-35 158 53 218 25v82H0V94Z" fill="currentColor" fill-opacity=".055"/>
                <path d="M0 128c171-40 286-14 428 15s246 28 390-15 277-64 400-18 170 52 222 26v44H0v-52Z" fill="currentColor" fill-opacity=".045"/>
                <path d="M0 94c178-40 286-44 438-6s244 65 399 20 264-73 385-35 158 53 218 25" stroke="currentColor" stroke-opacity=".2" stroke-width="2"/>
                <path d="M1060 123c115-27 226-15 380 28" stroke="#c28a4a" stroke-opacity=".24" stroke-width="2"/>
            </svg>
            <div class="homepage-hero-inner">
                <div class="homepage-hero-copy">
                    <p class="public-eyebrow"><span class="homepage-eyebrow-mark"></span> Kelab Staf Politeknik Besut</p>
                    <h1 id="homepage-title">Urus Kelab.<br><span>Eratkan Komuniti.</span></h1>
                    <p class="homepage-lead">Aktiviti, kebajikan dan urusan ahli dalam satu tempat. Luangkan lebih masa untuk komuniti yang menyatukan kita.</p>
                    <div class="homepage-actions">
                        <a class="btn btn-primary" href="{{ route('activities.index') }}">Lihat Aktiviti <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                        @guest<a class="btn btn-outline-primary" href="{{ route('login') }}">Log Masuk</a>@endguest
                    </div>
                    <div class="homepage-community-note"><i class="bi bi-heart-fill" aria-hidden="true"></i><span>Platform digital untuk warga POLIBEST</span></div>
                </div>

                <aside class="homepage-feature-card" aria-label="Aktiviti akan datang">
                    <div class="homepage-feature-topline"><span class="homepage-card-kicker">SERTAI KOMUNITI</span><span class="homepage-live-dot" aria-hidden="true"></span></div>
                    @if($upcomingActivities->isNotEmpty())
                        @php($featuredActivity = $upcomingActivities->first())
                        <div class="homepage-feature-art" aria-hidden="true"><span class="homepage-art-sun"></span><i class="bi bi-people-fill"></i><i class="bi bi-stars"></i></div>
                        <p class="homepage-card-label">AKTIVITI AKAN DATANG</p>
                        <h2>{{ $featuredActivity->title }}</h2>
                        <p class="homepage-event-meta"><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ $featuredActivity->date_time->format('d M Y · h:i A') }}</p>
                        @if($featuredActivity->location)<p class="homepage-event-meta"><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $featuredActivity->location }}</p>@endif
                        <a class="homepage-card-link" href="{{ route('activities.show', $featuredActivity) }}">Butiran aktiviti <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    @else
                        <div class="homepage-feature-art homepage-feature-art-empty" aria-hidden="true"><span class="homepage-art-sun"></span><i class="bi bi-calendar-heart"></i></div>
                        <p class="homepage-card-label">RUANG UNTUK BERKUMPUL</p>
                        <h2>Aktiviti baharu akan menyusul.</h2>
                        <p class="homepage-event-meta">Lihat halaman aktiviti untuk berita dan program terkini POLIBEST.</p>
                        <a class="homepage-card-link" href="{{ route('activities.index') }}">Terokai aktiviti <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    @endif
                    <div class="homepage-card-decoration" aria-hidden="true"><span></span><span></span><span></span></div>
                </aside>
            </div>
        </section>

        <div class="homepage-section-divider" aria-hidden="true"><span><svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="8.5" r="3.5" fill="currentColor"/><circle cx="6.5" cy="11" r="2.5" fill="currentColor"/><circle cx="25.5" cy="11" r="2.5" fill="currentColor"/><path d="M9.5 24c0-4.3 2.8-7.2 6.5-7.2s6.5 2.9 6.5 7.2v1h-13v-1ZM1.5 23c0-3.3 1.8-5.6 4.7-5.6 1.1 0 2 .3 2.8 1-1.5 1.4-2.4 3.5-2.4 6.1H1.5v-1.5ZM25.4 18.4c.8-.7 1.7-1 2.8-1 2.9 0 4.7 2.3 4.7 5.6v1.5h-5.1c0-2.6-.9-4.7-2.4-6.1Z" fill="currentColor"/></svg></span></div>

        <section id="aktiviti" class="homepage-activities" aria-labelledby="activities-title">
            <div class="homepage-section-heading">
                <div><p class="public-eyebrow">Program komuniti Poli</p><h2 id="activities-title">Aktiviti komuniti.</h2></div>
                <a class="homepage-text-link" href="{{ route('activities.index') }}">Semua aktiviti <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            @if($homepageActivities->isNotEmpty())
                <div class="homepage-carousel-controls" aria-label="Kawalan senarai aktiviti">
                    <button type="button" class="homepage-carousel-button" data-carousel-direction="prev" aria-label="Aktiviti sebelumnya"><i class="bi bi-arrow-left" aria-hidden="true"></i></button>
                    <button type="button" class="homepage-carousel-button" data-carousel-direction="next" aria-label="Aktiviti seterusnya"><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                </div>
                <div class="homepage-carousel" data-homepage-carousel tabindex="0" aria-label="Senarai semua aktiviti yang diluluskan">
                    <div class="homepage-activity-grid">
                @foreach($homepageActivities as $activity)
                    <article class="public-activity-card homepage-activity-card">
                        @php($previewPhoto = $activity->evidencePhotos->first())
                        <div class="homepage-activity-preview" aria-hidden="true">
                            @if($previewPhoto)
                                <img src="{{ asset('storage/'.$previewPhoto->path) }}" alt="" loading="lazy">
                            @else
                                <span class="homepage-preview-sun"></span><i class="bi bi-people-fill"></i><i class="bi bi-stars"></i>
                            @endif
                        </div>
                        <div class="homepage-activity-card-body">
                            <div class="public-activity-date"><strong>{{ $activity->date_time->format('d') }}</strong><span>{{ mb_strtoupper($activity->date_time->format('M')) }}</span></div>
                            <div><p class="public-activity-meta">{{ $activity->date_time->format('d/m/Y · h:i A') }}@if($activity->location) · {{ $activity->location }}@endif</p><h3>{{ $activity->title }}</h3><span class="homepage-activity-status">{{ $activity->date_time->isPast() ? 'Telah dijalankan' : 'Akan datang' }}</span><a class="homepage-text-link" href="{{ route('activities.show', $activity) }}">Lihat butiran <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                        </div>
                    </article>
                @endforeach
                    </div>
                </div>
            @else
                    <div class="homepage-no-events"><span class="homepage-empty-icon"><i class="bi bi-calendar-heart" aria-hidden="true"></i></span><div><h3>Belum ada aktiviti dijadualkan.</h3><p>Semak semula nanti atau terokai semua maklumat aktiviti.</p></div><a class="homepage-text-link" href="{{ route('activities.index') }}">Terokai aktiviti <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
            @endif
        </section>

        <section id="tentang" class="homepage-about" aria-labelledby="about-title">
            <div class="homepage-about-heading"><p class="public-eyebrow">Satu komuniti, satu tempat</p><h2 id="about-title">Semua urusan kelab,<br><span>lebih mudah diurus.</span></h2><p>POLISTAFF menyatukan maklumat dan urusan harian Kelab Staf Politeknik Besut supaya ahli boleh kekal terhubung.</p></div>
            <div class="homepage-benefits">
                <article class="homepage-benefit"><span class="homepage-benefit-icon"><i class="bi bi-people" aria-hidden="true"></i></span><h3>Ahli</h3><p>Maklumat komuniti dan keahlian yang tersusun.</p></article>
                <article class="homepage-benefit"><span class="homepage-benefit-icon"><i class="bi bi-calendar2-heart" aria-hidden="true"></i></span><h3>Aktiviti</h3><p>Terokai program dan sertai kegiatan kelab.</p></article>
                <article class="homepage-benefit"><span class="homepage-benefit-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span><h3>Urusan kelab</h3><p>Proses dan maklumat kelab dalam satu sistem.</p></article>
            </div>
        </section>

        <section id="polimart" class="homepage-market" aria-labelledby="market-title">
            <div class="homepage-market-icon" aria-hidden="true"><i class="bi bi-bag-heart"></i></div>
            <div><p class="public-eyebrow">Ruang jual beli komuniti</p><h2 id="market-title">Kenali PoliMart.</h2><p>Temui barangan pilihan kelab dan sokong aktiviti komuniti.</p></div>
            <a class="btn btn-primary" href="{{ route('polimart.index') }}#produk">Lihat produk <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
        </section>
    </main>

    @include('partials.site-footer')
</div>
</body>
</html>
