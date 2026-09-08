<!doctype html>
<html lang="ms" data-theme-preference="light" data-authenticated="false">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#eee5d8">
    <title>{{ $code }} &middot; {{ $title }} &middot; POLISTAFF</title>
    @include('partials.theme-loader')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="error-page">
        <section class="error-panel">
            <header class="error-brand">
                <a href="{{ url('/') }}" aria-label="Halaman utama POLISTAFF">
                    @include('partials.brand-mark')
                    <span>
                        <strong>POLISTAFF</strong>
                        <small>Portal Pengurusan Kelab Staf</small>
                    </span>
                </a>
                <span class="error-status">Ralat {{ $code }}</span>
            </header>

            <div class="error-content">
                <div class="error-symbol" aria-hidden="true">
                    <i class="bi {{ $icon }}"></i>
                </div>
                <span class="section-kicker">{{ $kicker }}</span>
                <h1>{{ $heading }}</h1>
                <p>{{ $message }}</p>

                <div class="error-actions">
                    <a class="error-primary-action" href="{{ $actionUrl }}">
                        <i class="bi {{ $actionIcon }}" aria-hidden="true"></i>
                        {{ $actionLabel }}
                    </a>
                    @if($actionUrl !== url('/'))
                        <a class="error-secondary-action" href="{{ url('/') }}">Halaman utama</a>
                    @endif
                </div>
            </div>

            <footer>
                <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
            </footer>
        </section>
    </main>
</body>
</html>
