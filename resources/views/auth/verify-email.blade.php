<!doctype html>
<html lang="ms"
    data-theme-preference="{{ auth()->user()->theme_preference }}"
    data-authenticated="true"
    data-text-size="{{ auth()->user()->text_size_preference }}"
    @if(auth()->user()->reduce_motion) data-reduce-motion="true" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#eee5d8">
    <title>Sahkan Alamat Emel &middot; POLISTAFF</title>
    @include('partials.theme-loader')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="verification-page">
        <section class="verification-panel">
            <header class="verification-brand">
                @include('partials.brand-mark')
                <span>
                    <strong>POLISTAFF</strong>
                    <small>Portal Pengurusan Kelab Staf</small>
                </span>
            </header>

            @if(session('status'))
                <div class="alert alert-success auto-dismiss-alert" role="status">
                    <i class="bi bi-check-circle me-2" aria-hidden="true"></i>{{ session('status') }}
                </div>
            @endif

            <div class="verification-icon"><i class="bi bi-envelope-check" aria-hidden="true"></i></div>
            <span class="section-kicker">Pengesahan Akaun</span>
            <h1>Semak peti masuk anda</h1>
            <p>Kami telah menghantar pautan pengesahan ke:</p>
            <strong class="verification-email">{{ auth()->user()->email }}</strong>
            <p class="verification-note">Klik pautan dalam emel untuk membuka akses penuh ke portal. Pautan sah selama 60 minit.</p>

            <div class="verification-actions">
                <form method="post" action="{{ route('verification.send') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-send me-1" aria-hidden="true"></i>
                        Hantar Semula
                    </button>
                </form>
                <a class="btn btn-outline-primary" href="{{ route('profile.edit') }}">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                    Betulkan Alamat Emel
                </a>
            </div>

            <form class="verification-logout" method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log keluar</button>
            </form>
        </section>
    </main>

    <script>
        document.querySelectorAll('.auto-dismiss-alert').forEach((alert) => {
            window.setTimeout(() => {
                alert.classList.add('is-dismissing');
                window.setTimeout(() => alert.remove(), 650);
            }, 3000);
        });
    </script>
</body>
</html>
