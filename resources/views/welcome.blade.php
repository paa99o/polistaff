<!doctype html>
<html lang="ms"
    data-theme-preference="{{ auth()->check() ? auth()->user()->theme_preference : 'light' }}"
    data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
    data-text-size="{{ auth()->check() ? auth()->user()->text_size_preference : 'normal' }}"
    @if(auth()->check() && auth()->user()->reduce_motion) data-reduce-motion="true" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="POLIBEST — Portal Pengurusan Kelab Staf">
    <title>POLIBEST · Portal Pengurusan Kelab Staf</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <meta name="theme-color" content="#1557D8">
    @include('partials.theme-loader')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="public-shell">
    <header class="public-nav">
        <div class="public-container d-flex align-items-center justify-content-between w-100">
            <a class="public-brand" href="{{ url('/') }}">
                @include('partials.brand-mark')
                <span>
                    <span class="brand-name">POLIBEST</span>
                    <span class="brand-description text-muted">Portal Pengurusan Kelab Staf</span>
                </span>
            </a>
            <nav class="public-links" aria-label="Navigasi utama">
                <a href="{{ route('activities.index') }}">Aktiviti</a>
                <a href="{{ route('polimart.index') }}">PoliMart</a>
                @auth
                    <a class="public-nav-button" href="{{ route('dashboard') }}">Dashboard</a>
                @else
                    <a class="public-nav-button" href="{{ route('login') }}">Log Masuk</a>
                @endauth
            </nav>
            @include('partials.theme-switcher')
        </div>
    </header>

    <main>
        <section class="public-container public-hero">
            <div>
                <div class="auth-stripe mb-4" aria-hidden="true"><span></span><span></span><span></span></div>
                <p class="public-eyebrow">Mengenali POLIBEST</p>
                <h1 class="public-title brand-hero-title" aria-label="POLIBEST">
                    <span>POLI</span><span>BEST</span>
                </h1>
                <p class="public-lead">POLIBEST merupakan ruang kebersamaan warga Politeknik yang menyokong hubungan, aktiviti dan kesejahteraan komuniti staf.</p>
                <div class="d-flex flex-wrap gap-3">
                    @auth
                        <a class="btn btn-primary px-4" href="{{ route('dashboard') }}">Buka Dashboard <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                    @else
                        <a class="btn btn-primary px-4" href="#tentang">Kenali POLIBEST <i class="bi bi-arrow-down ms-2" aria-hidden="true"></i></a>
                        <a class="btn btn-outline-primary px-4" href="{{ route('polimart.index') }}">Lihat PoliMart</a>
                    @endauth
                </div>
            </div>

            <aside class="public-technical-panel" aria-label="Ringkasan POLIBEST">
                <div class="public-panel-heading">
                    <h2>Tentang POLIBEST</h2>
                    <span class="badge"><i class="bi bi-heart" aria-hidden="true"></i> Komuniti</span>
                </div>
                <div class="public-metric-grid">
                    <div class="public-metric"><i class="bi bi-people" aria-hidden="true"></i><strong>Komuniti</strong><span>Menghubungkan warga staf</span></div>
                    <div class="public-metric"><i class="bi bi-calendar3" aria-hidden="true"></i><strong>Aktiviti</strong><span>Program dan kegiatan kelab</span></div>
                    <div class="public-metric"><i class="bi bi-heart-pulse" aria-hidden="true"></i><strong>Kebajikan</strong><span>Menyokong kesejahteraan ahli</span></div>
                    <div class="public-metric"><i class="bi bi-bag-heart" aria-hidden="true"></i><strong>PoliMart</strong><span>Pilihan barangan komuniti</span></div>
                </div>
            </aside>
        </section>

        <section id="tentang" class="public-section">
            <div class="public-container">
                <div class="public-section-header">
                    <h2>Mengenali POLIBEST.</h2>
                    <p>POLIBEST menghimpunkan warga staf dalam sebuah komuniti yang mengutamakan kebersamaan, penglibatan dan kesejahteraan bersama.</p>
                </div>
                <div id="keupayaan" class="public-feature-grid">
                    <article class="public-feature">
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <h3>Komuniti Staf</h3>
                        <p>Mewujudkan ruang untuk warga staf berhubung dan mengeratkan hubungan sesama komuniti.</p>
                    </article>
                    <article class="public-feature">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <h3>Aktiviti Bersama</h3>
                        <p>Ikuti program dan aktiviti yang dijalankan untuk menggalakkan penglibatan warga POLIBEST.</p>
                    </article>
                    <article class="public-feature">
                        <i class="bi bi-heart-pulse" aria-hidden="true"></i>
                        <h3>Kesejahteraan Ahli</h3>
                        <p>Menyokong kebajikan dan kesejahteraan ahli melalui inisiatif serta program kelab.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="aktiviti" class="public-section">
            <div class="public-container">
                <div class="public-section-header">
                    <div>
                        <p class="public-eyebrow mb-3">Program komuniti Poli</p>
                        <h2>Aktiviti akan datang.</h2>
                    </div>
                    <div>
                        <p>Lihat program yang akan datang dan dapatkan maklumat penyertaan sebagai tetamu awam.</p>
                        <a class="btn btn-outline-primary" href="{{ route('activities.index') }}">Lihat Semua Aktiviti <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                    </div>
                </div>
                <div class="public-activity-grid">
                    @forelse($upcomingActivities as $activity)
                        <article class="public-activity-card">
                            <div class="public-activity-date"><strong>{{ $activity->date_time->format('d') }}</strong><span>{{ mb_strtoupper($activity->date_time->format('M')) }}</span></div>
                            <div>
                                <p class="public-activity-meta">{{ $activity->date_time->format('d/m/Y · h:i A') }} · {{ $activity->location }}</p>
                                <h2>{{ $activity->title }}</h2>
                                <p>{{ $activity->description ?: 'Maklumat aktiviti akan dikemas kini oleh pihak penganjur.' }}</p>
                                <a class="btn btn-outline-primary" href="{{ route('activities.show', $activity) }}">Lihat Aktiviti <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                            </div>
                        </article>
                    @empty
                        <div class="public-empty-state"><i class="bi bi-calendar3" aria-hidden="true"></i><h2>Tiada aktiviti akan datang.</h2><p>Sila kembali semula untuk kemas kini program POLIBEST.</p></div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="polimart" class="public-section public-market-section">
            <div class="public-container public-market-layout">
                <div>
                    <p class="public-eyebrow">Ruang jual beli komuniti</p>
                    <h2>Pilihan PoliMart untuk warga POLIBEST.</h2>
                </div>
                <div>
                    <p>Temui barangan pilihan kelab dan sokong aktiviti komuniti melalui ruang jual beli PoliMart.</p>
                    <a class="btn btn-primary" href="{{ route('polimart.index') }}#produk">Lihat Produk <i class="bi bi-arrow-down ms-2" aria-hidden="true"></i></a>
                </div>
            </div>
        </section>
    </main>

    @include('partials.site-footer')
</div>
</body>
</html>
