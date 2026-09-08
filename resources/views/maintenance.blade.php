<!doctype html>
<html lang="ms" data-theme-preference="light" data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#eee5d8">
    <title>Penyelenggaraan Sistem &middot; POLISTAFF</title>
    @include('partials.theme-loader')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="maintenance-page">
        <section class="maintenance-panel">
            <div class="maintenance-brand">
                @include('partials.brand-mark')
                <div>
                    <strong>POLISTAFF</strong>
                    <span>Portal Pengurusan Kelab Staf</span>
                </div>
            </div>

            <div class="maintenance-icon"><i class="bi bi-tools" aria-hidden="true"></i></div>
            <span class="section-kicker">Penyelenggaraan Sistem</span>
            <h1>Kami akan kembali sebentar lagi</h1>
            <p class="maintenance-message">{{ $message }}</p>

            @if($estimatedEnd)
                <div class="maintenance-estimate">
                    <i class="bi bi-clock" aria-hidden="true"></i>
                    <span>
                        <small>Anggaran selesai</small>
                        <strong>{{ \Carbon\Carbon::parse($estimatedEnd)->format('d/m/Y h:i A') }}</strong>
                    </span>
                </div>
            @endif

            <p class="maintenance-contact">Perlukan bantuan? <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></p>

            @auth
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary" type="submit">Log Keluar</button>
                </form>
            @else
                <a class="maintenance-admin-link" href="{{ route('login') }}">Log masuk pentadbir</a>
            @endauth
        </section>
    </main>
</body>
</html>
