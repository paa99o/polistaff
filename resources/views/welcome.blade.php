<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="POLISTAFF — Portal Pengurusan Kelab Staf">
    <title>POLISTAFF · Portal Pengurusan Kelab Staf</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <meta name="theme-color" content="#1557D8">
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
                    <span class="brand-name">POLISTAFF</span>
                    <span class="brand-description text-muted">Portal Pengurusan Kelab Staf</span>
                </span>
            </a>
        </div>
    </header>

    <main>
        <section class="public-container public-hero">
            <div>
                <div class="auth-stripe mb-4" aria-hidden="true"><span></span><span></span><span></span></div>
                <p class="public-eyebrow">Pengurusan kelab staf yang tersusun</p>
                <h1 class="public-title brand-hero-title" aria-label="POLISTAFF">
                    <span>POLI</span><span>STAFF</span>
                </h1>
                <p class="public-lead">Urus keahlian, aktiviti, bayaran, tuntutan dan kehadiran melalui pengalaman digital yang jelas dan tepat.</p>
                <div class="d-flex flex-wrap gap-3">
                    @auth
                        <a class="btn btn-primary px-4" href="{{ route('dashboard') }}">Buka Dashboard <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                    @else
                        <a class="btn btn-primary px-4" href="{{ route('login') }}">Log Masuk <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                        <a class="btn btn-outline-primary px-4" href="{{ route('register') }}">Daftar Sebagai Ahli</a>
                    @endauth
                </div>
            </div>

            <aside class="public-technical-panel" aria-label="Ringkasan fungsi portal">
                <div class="public-panel-heading">
                    <h2>Keupayaan Portal</h2>
                    <span class="badge"><i class="bi bi-check-circle" aria-hidden="true"></i> Bersepadu</span>
                </div>
                <div class="public-metric-grid">
                    <div class="public-metric"><i class="bi bi-people" aria-hidden="true"></i><strong>Keahlian</strong><span>Pendaftaran dan kelulusan</span></div>
                    <div class="public-metric"><i class="bi bi-wallet2" aria-hidden="true"></i><strong>Bayaran</strong><span>Yuran, bukti dan resit</span></div>
                    <div class="public-metric"><i class="bi bi-qr-code-scan" aria-hidden="true"></i><strong>Kehadiran</strong><span>Imbasan QR yang pantas</span></div>
                    <div class="public-metric"><i class="bi bi-bar-chart" aria-hidden="true"></i><strong>Laporan</strong><span>Rekod yang boleh dijejak</span></div>
                </div>
            </aside>
        </section>

        <section class="public-section">
            <div class="public-container">
                <div class="public-section-header">
                    <h2>Dibina untuk operasi sebenar.</h2>
                    <p>Setiap aliran kerja mengurangkan rekod manual, mempercepat kelulusan dan memastikan maklumat penting boleh dicapai oleh pihak yang betul.</p>
                </div>
                <div class="public-feature-grid">
                    <article class="public-feature">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <h3>Aktiviti & Kehadiran</h3>
                        <p>Terbitkan aktiviti, urus pendaftaran dan rekod kehadiran melalui kod QR.</p>
                    </article>
                    <article class="public-feature">
                        <i class="bi bi-receipt" aria-hidden="true"></i>
                        <h3>Kewangan Terkawal</h3>
                        <p>Semak bukti bayaran, hasilkan resit dan pantau transaksi secara berpusat.</p>
                    </article>
                    <article class="public-feature">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <h3>Rekod Boleh Diaudit</h3>
                        <p>Jejaki perubahan penting dan sediakan laporan untuk pengurusan dengan yakin.</p>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer class="public-footer">
        <div class="public-container d-flex flex-column flex-md-row gap-4 justify-content-between align-items-md-center">
            <div>
                <strong>POLISTAFF</strong>
                <div class="small opacity-75">© {{ date('Y') }} Portal Pengurusan Kelab Staf</div>
            </div>
            <nav class="d-flex flex-wrap gap-4" aria-label="Pautan kaki halaman">
                <a href="{{ route('login') }}">Log Masuk</a>
                <a href="{{ route('register') }}">Daftar</a>
                <a href="#">Privasi</a>
                <a href="#">Terma</a>
                <a href="mailto:admin@polistaff.local">Hubungi</a>
            </nav>
        </div>
    </footer>
</div>
</body>
</html>
