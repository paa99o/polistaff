<!doctype html>
<html lang="ms"
    data-theme-preference="{{ auth()->check() ? auth()->user()->theme_preference : 'light' }}"
    data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
    data-text-size="{{ auth()->check() ? auth()->user()->text_size_preference : 'normal' }}"
    @if(auth()->check() && auth()->user()->reduce_motion) data-reduce-motion="true" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="POLISTAFF membantu pengurusan Kelab Staf Politeknik Besut secara sistematik, mudah dan telus.">
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
        {{-- 1 · Introduction --}}
        <section class="public-container public-hero story-hero" aria-labelledby="story-hero-title">
            <div class="story-reveal">
                <div class="auth-stripe mb-4" aria-hidden="true"><span></span><span></span><span></span></div>
                <p class="public-eyebrow">Sistem Pengurusan Kelab Staf</p>
                <h1 id="story-hero-title" class="public-title brand-hero-title" aria-label="POLIBEST"><span>POLI</span><span>BEST</span></h1>
                <p class="story-hero-subtitle">Staff Club Management System</p>
                <p class="public-lead">Platform digital yang membantu pengurusan Kelab Staf Politeknik Besut menjadi lebih sistematik, mudah dan telus.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-primary px-4" href="#tentang">Terokai POLISTAFF <i class="bi bi-arrow-down ms-2" aria-hidden="true"></i></a>
                    @guest<a class="btn btn-outline-primary px-4" href="{{ route('login') }}">Log Masuk</a>@endguest
                </div>
            </div>
            <aside class="public-technical-panel story-reveal" aria-label="Ringkasan POLIBEST">
                <div class="public-panel-heading"><h2>Kelab Staf Politeknik Besut</h2><span class="badge"><i class="bi bi-heart" aria-hidden="true"></i> Komuniti</span></div>
                <div class="public-metric-grid">
                    <div class="public-metric"><i class="bi bi-people" aria-hidden="true"></i><strong>Komuniti</strong><span>Menghubungkan warga staf</span></div>
                    <div class="public-metric"><i class="bi bi-calendar3" aria-hidden="true"></i><strong>Aktiviti</strong><span>Program dan kegiatan kelab</span></div>
                    <div class="public-metric"><i class="bi bi-heart-pulse" aria-hidden="true"></i><strong>Kebajikan</strong><span>Menyokong kesejahteraan ahli</span></div>
                    <div class="public-metric"><i class="bi bi-shield-check" aria-hidden="true"></i><strong>Pengurusan</strong><span>Maklumat dalam satu platform</span></div>
                </div>
            </aside>
        </section>

        {{-- 2 · Identity --}}
        <section id="tentang" class="public-section public-about-section story-section">
            <video class="public-about-video" autoplay muted loop playsinline aria-hidden="true"><source src="{{ asset('videos/polibest-about.mp4') }}" type="video/mp4"></video>
            <div class="public-about-overlay" aria-hidden="true"></div>
            <div class="public-container">
                <div class="story-chapter story-reveal"><span>01 / IDENTITI</span><span>Kenali sistem</span></div>
                <div class="public-section-header story-reveal">
                    <div><p class="public-eyebrow mb-3">Mengenali POLISTAFF</p><h2>Apa Itu POLISTAFF?</h2></div>
                    <p>POLIBEST ialah sistem pengurusan digital yang dibangunkan khas untuk membantu pengurusan Kelab Staf Politeknik Besut. Daripada urusan ahli dan aktiviti hinggalah kewangan, semuanya disusun dalam satu tempat.</p>
                </div>
                <div class="story-identity-band story-reveal"><i class="bi bi-diagram-3" aria-hidden="true"></i><p><strong>Satu platform, satu komuniti.</strong><br>Maklumat lebih mudah dicapai oleh orang yang tepat, pada masa yang diperlukan.</p></div>
            </div>
        </section>

        {{-- 3 · The problem --}}
        <section class="public-section story-section story-problem-section">
            <div class="public-container">
                <div class="story-chapter story-reveal"><span>02 / CABARAN</span><span>Sebelum pendigitalan</span></div>
                <div class="public-section-header story-reveal"><div><p class="public-eyebrow mb-3">Kenapa perubahan diperlukan?</p><h2>Cabaran Pengurusan Secara Manual</h2></div><p>Apabila rekod bertaburan dan proses bergantung pada kerja manual, urusan harian kelab mengambil lebih banyak masa dan sukar dipantau.</p></div>
                <div class="story-card-grid story-problem-grid">
                    @foreach([
                        ['bi-folder-minus', 'Rekod Tidak Tersusun', 'Maklumat ahli, kewangan dan aktiviti disimpan secara manual menyebabkan proses pencarian maklumat menjadi lambat.'],
                        ['bi-search', 'Kesukaran Akses Maklumat', 'Data ahli dan rekod pembayaran sukar dicapai apabila diperlukan.'],
                        ['bi-clipboard2-x', 'Rekod Kehadiran Tidak Sistematik', 'Kehadiran aktiviti menggunakan rekod manual menyebabkan data sukar dianalisis.'],
                        ['bi-exclamation-triangle', 'Risiko Kehilangan Data', 'Tiada penyimpanan data berpusat menyebabkan risiko kehilangan maklumat penting.'],
                    ] as $index => [$icon, $title, $description])
                        <article class="story-card story-reveal" style="--story-order: {{ $index }}"><span class="story-card-number">0{{ $index + 1 }}</span><i class="bi {{ $icon }}" aria-hidden="true"></i><h3>{{ $title }}</h3><p>{{ $description }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 4 · The transformation --}}
        <section class="public-section story-section story-transform-section">
            <div class="public-container">
                <div class="story-chapter story-reveal"><span>03 / PERUBAHAN</span><span>Daripada cabaran kepada penyelesaian</span></div>
                <div class="public-section-header story-reveal"><div><p class="public-eyebrow mb-3">Transformasi pengurusan</p><h2>Dari Manual Kepada Digital</h2></div><p>POLISTAFF menghubungkan rekod dan proses dalam satu aliran kerja yang lebih mudah diikuti.</p></div>
                <div class="story-compare-grid story-reveal">
                    <article class="story-compare-card story-before"><span class="story-compare-label"><i class="bi bi-clock-history" aria-hidden="true"></i> SEBELUM</span><ul><li>Fail fizikal</li><li>Rekod manual</li><li>Semakan lambat</li><li>Data berasingan</li></ul></article>
                    <div class="story-transform-arrow" aria-hidden="true"><i class="bi bi-arrow-right"></i></div>
                    <article class="story-compare-card story-after"><span class="story-compare-label"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> DENGAN POLISTAFF</span><ul><li>Database berpusat</li><li>Maklumat mudah dicapai</li><li>Pengurusan lebih automatik</li><li>Data lebih tersusun</li></ul></article>
                </div>
            </div>
        </section>

        {{-- 5 · How it works --}}
        <section class="public-section story-section story-workflow-section">
            <div class="public-container">
                <div class="story-chapter story-reveal"><span>04 / ALIRAN KERJA</span><span>Pengurusan dari mula ke akhir</span></div>
                <div class="public-section-header story-reveal"><div><p class="public-eyebrow mb-3">Perjalanan pengguna</p><h2>Bagaimana POLISTAFF Berfungsi?</h2></div><p>Setiap langkah saling berkait supaya perjalanan daripada penglibatan ahli hingga dokumentasi lebih jelas.</p></div>
                <ol class="story-timeline">
                    @foreach([['bi-person-check', 'Pengguna mendaftar dan mengurus maklumat', 'Ahli mengakses profil dan urusan kelab mengikut peranan.'], ['bi-calendar-plus', 'Aktiviti kelab dicipta melalui sistem', 'Staf mengisi maklumat program dan menghantar permohonan.'], ['bi-people', 'Ahli melihat dan menyertai aktiviti', 'Maklumat aktiviti yang diluluskan tersedia untuk komuniti.'], ['bi-qr-code-scan', 'Kehadiran direkodkan', 'Rekod kehadiran disimpan untuk rujukan dan laporan.'], ['bi-file-earmark-text', 'Laporan dan dokumen dijana secara digital', 'Maklumat tersusun boleh digunakan untuk dokumentasi program.']] as $index => [$icon, $title, $description])
                        <li class="story-timeline-item story-reveal" style="--story-order: {{ $index }}"><span class="story-step-number">{{ $index + 1 }}</span><span class="story-step-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span><div><h3>{{ $title }}</h3><p>{{ $description }}</p></div></li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- 6 · Activity proposal paperwork --}}
        <section class="public-section story-section story-paperwork-section">
            <div class="public-container">
                <div class="story-chapter story-reveal"><span>05 / DOKUMENTASI</span><span>Daripada borang kepada dokumen</span></div>
                <div class="public-section-header story-reveal"><div><p class="public-eyebrow mb-3">Satu aliran yang tersusun</p><h2>Dari Borang Aktiviti Kepada Kertas Kerja</h2></div><p>POLISTAFF membantu staf menyediakan dokumen rasmi dengan lebih cepat melalui penjanaan kertas kerja berdasarkan maklumat aktiviti yang dimasukkan.</p></div>
                <div class="story-paperwork-flow">
                    @foreach([['bi-pencil-square', 'Daftar Aktiviti', 'Isi nama program, tarikh, lokasi, objektif, tentatif, bajet dan jawatankuasa.'], ['bi-layout-text-window', 'Sistem Menyusun Maklumat', 'Maklumat borang dipetakan ke bahagian dokumen dalam format rasmi.'], ['bi-file-earmark-pdf', 'Jana Kertas Kerja', 'Pratonton, semak maklumat yang kurang dan sunting bahagian teks dokumen.'], ['bi-check2-square', 'Semakan & Kelulusan', 'Permohonan meneruskan proses semakan bendahari dan kelulusan admin.']] as $index => [$icon, $title, $description])
                        <article class="story-paperwork-step story-reveal" style="--story-order: {{ $index }}"><span class="story-step-number">{{ $index + 1 }}</span><i class="bi {{ $icon }}" aria-hidden="true"></i><h3>{{ $title }}</h3><p>{{ $description }}</p>@if($index < 3)<span class="story-paperwork-connector" aria-hidden="true"><i class="bi bi-arrow-down"></i></span>@endif</article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 7 · Modules --}}
        <section class="public-section story-section story-modules-section">
            <div class="public-container">
                <div class="story-chapter story-reveal"><span>06 / KEUPAYAAN</span><span>Modul utama</span></div>
                <div class="public-section-header story-reveal"><div><p class="public-eyebrow mb-3">Satu sistem untuk semua keperluan kelab</p><h2>Satu Sistem Untuk Semua Keperluan Kelab</h2></div><p>Modul POLISTAFF menyokong pengurusan dan komunikasi harian komuniti POLIBEST.</p></div>
                <div class="story-card-grid story-module-grid">
                    @foreach([['bi-person-vcard', 'Pengurusan Ahli', 'Urus pendaftaran dan maklumat ahli secara digital.'], ['bi-calendar-event', 'Pengurusan Aktiviti', 'Rancang program, permohonan dan penyertaan aktiviti kelab.'], ['bi-clipboard2-check', 'Pengurusan Kehadiran', 'Rekod kehadiran dengan QR dan semak rekod program.'], ['bi-wallet2', 'Pengurusan Kewangan', 'Urus yuran, transaksi, tuntutan dan permohonan sumbangan.'], ['bi-bell', 'Sistem Notifikasi', 'Sampaikan maklumat penting dan kemas kini status permohonan.'], ['bi-speedometer2', 'Dashboard', 'Paparkan ringkasan maklumat dalam satu paparan.']] as $index => [$icon, $title, $description])
                        <article class="story-card story-module-card story-reveal" style="--story-order: {{ $index }}"><i class="bi {{ $icon }}" aria-hidden="true"></i><h3>{{ $title }}</h3><p>{{ $description }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Existing upcoming activities component retained --}}
        <section id="aktiviti" class="public-section story-existing-section">
            <div class="public-container">
                <div class="public-section-header story-reveal"><div><p class="public-eyebrow mb-3">Program komuniti Poli</p><h2>Aktiviti akan datang.</h2></div><div><p>Lihat program yang akan datang dan dapatkan maklumat penyertaan sebagai tetamu awam.</p><a class="btn btn-outline-primary" href="{{ route('activities.index') }}">Lihat Semua Aktiviti <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a></div></div>
                <div class="public-activity-grid">
                    @forelse($upcomingActivities as $activity)
                        <article class="public-activity-card story-reveal"><div class="public-activity-date"><strong>{{ $activity->date_time->format('d') }}</strong><span>{{ mb_strtoupper($activity->date_time->format('M')) }}</span></div><div><p class="public-activity-meta">{{ $activity->date_time->format('d/m/Y · h:i A') }} · {{ $activity->location }}</p><h2>{{ $activity->title }}</h2><a class="btn btn-outline-primary" href="{{ route('activities.show', $activity) }}">Lihat Aktiviti <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a></div></article>
                    @empty
                        <div class="public-empty-state"><i class="bi bi-calendar3" aria-hidden="true"></i><h2>Tiada aktiviti akan datang.</h2><p>Sila kembali semula untuk kemas kini program POLIBEST.</p></div>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Existing PoliMart component retained --}}
        <section id="polimart" class="public-section public-market-section story-existing-section">
            <div class="public-container public-market-layout story-reveal"><div><p class="public-eyebrow">Ruang jual beli komuniti</p><h2>Pilihan PoliMart untuk warga POLIBEST.</h2></div><div><p>Temui barangan pilihan kelab dan sokong aktiviti komuniti melalui ruang jual beli PoliMart.</p><a class="btn btn-primary" href="{{ route('polimart.index') }}#produk">Lihat Produk <i class="bi bi-arrow-down ms-2" aria-hidden="true"></i></a></div></div>
        </section>

        {{-- 8 · Roles --}}
        <section class="public-section story-section story-roles-section">
            <div class="public-container">
                <div class="story-chapter story-reveal"><span>07 / PERANAN</span><span>Kerja yang jelas untuk semua</span></div>
                <div class="public-section-header story-reveal"><div><p class="public-eyebrow mb-3">Akses mengikut tanggungjawab</p><h2>Direka Untuk Setiap Peranan</h2></div><p>Setiap peranan melihat tindakan dan fungsi yang sepadan dengan tanggungjawabnya dalam sistem.</p></div>
                <div class="story-role-grid">
                    <article class="story-role-card story-reveal"><span class="story-role-icon"><i class="bi bi-person" aria-hidden="true"></i></span><h3>Staff / Ahli</h3><ul><li>Urus profil dan keahlian.</li><li>Daftar aktiviti dan jana pratonton kertas kerja.</li><li>Sertai aktiviti dan semak status permohonan.</li><li>Hantar bayaran yuran, tuntutan dan permohonan sumbangan.</li></ul></article>
                    <article class="story-role-card story-reveal"><span class="story-role-icon"><i class="bi bi-calculator" aria-hidden="true"></i></span><h3>Bendahari</h3><ul><li>Urus yuran dan rekod kewangan kelab.</li><li>Semak serta sokong tuntutan dan sumbangan.</li><li>Tetapkan had sumbangan dan semak permohonan aktiviti.</li><li>Urus transaksi, laporan kewangan dan kehadiran aktiviti.</li></ul></article>
                    <article class="story-role-card story-reveal"><span class="story-role-icon"><i class="bi bi-person-gear" aria-hidden="true"></i></span><h3>Admin / Pengerusi</h3><ul><li>Urus ahli dan tetapan sistem.</li><li>Luluskan aktiviti selepas semakan bendahari.</li><li>Tentukan kelulusan akhir tuntutan dan sumbangan.</li><li>Pantau transaksi, notifikasi dan laporan sistem.</li></ul></article>
                </div>
            </div>
        </section>

        {{-- 9 · Impact --}}
        <section class="public-section story-section story-impact-section">
            <div class="public-container">
                <div class="story-chapter story-reveal"><span>08 / KESAN</span><span>Nilai kepada komuniti</span></div>
                <div class="public-section-header story-reveal"><div><p class="public-eyebrow mb-3">Manfaat yang dirasai</p><h2>Kesan POLISTAFF</h2></div><p>Pengurusan yang tersusun membantu warga kelab fokus pada perkara paling penting—membina komuniti staf yang aktif dan sejahtera.</p></div>
                <div class="story-impact-grid">
                    @foreach([['bi-lightning-charge', 'Kecekapan', 'Mempercepatkan proses pengurusan kelab.'], ['bi-eye', 'Ketelusan', 'Maklumat lebih mudah dicapai dan dipantau.'], ['bi-shield-lock', 'Keselamatan', 'Data disimpan secara lebih selamat melalui akses berperanan.'], ['bi-emoji-smile', 'Pengalaman Ahli', 'Ahli mendapat akses maklumat dengan lebih mudah.']] as $index => [$icon, $title, $description])
                        <article class="story-impact-item story-reveal" style="--story-order: {{ $index }}"><i class="bi {{ $icon }}" aria-hidden="true"></i><h3>{{ $title }}</h3><p>{{ $description }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 10 · Future --}}
        <section class="public-section story-section story-future-section">
            <div class="public-container story-future-layout">
                <div class="story-reveal"><div class="story-chapter"><span>09 / LANGKAH SETERUSNYA</span><span>Terus berkembang</span></div><p class="public-eyebrow mb-3">Masa depan komuniti digital</p><h2>Masa Depan POLISTAFF</h2><p class="story-future-copy">POLISTAFF akan terus berkembang sebagai platform digital yang menyokong komuniti staf yang lebih connected.</p></div>
                <div class="story-future-panel story-reveal"><h3>Aspirasi akan datang</h3><ul><li><i class="bi bi-phone" aria-hidden="true"></i> Aplikasi mudah alih</li><li><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> Analitik lanjutan</li><li><i class="bi bi-credit-card" aria-hidden="true"></i> Integrasi bayaran dalam talian</li><li><i class="bi bi-stars" aria-hidden="true"></i> Lebih banyak automasi AI</li></ul><p class="small mb-0">Ini ialah hala tuju masa depan, bukan fungsi yang tersedia sekarang.</p></div>
            </div>
        </section>
    </main>

    @include('partials.site-footer')
</div>
</body>
</html>
