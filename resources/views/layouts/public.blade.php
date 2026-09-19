<!doctype html>
<html lang="ms" data-theme-preference="light" data-authenticated="false">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="POLIBEST — PoliMart dan aktiviti komuniti">
    <title>{{ $title ?? 'POLIBEST' }} · POLIBEST</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @include('partials.theme-loader')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="public-shell public-storefront-shell">
        <header class="public-nav">
            <div class="public-container d-flex align-items-center justify-content-between w-100">
                <a class="public-brand" href="{{ url('/') }}">
                    @include('partials.brand-mark')
                    <span>
                        <span class="brand-name">POLIBEST</span>
                        <span class="brand-description">Portal Pengurusan Kelab Staf</span>
                    </span>
                </a>
                <nav class="public-links" aria-label="Navigasi awam">
                    <a class="{{ request()->routeIs('activities.*') ? 'public-nav-active' : '' }}" href="{{ route('activities.index') }}">Aktiviti</a>
                    <a class="{{ request()->routeIs('polimart.*') ? 'public-nav-active' : '' }}" href="{{ route('polimart.index') }}"><i class="bi bi-bag me-1" aria-hidden="true"></i>PoliMart</a>
                    <a class="public-cart-link" href="{{ route('polimart.cart') }}"><i class="bi bi-cart3 me-1" aria-hidden="true"></i>Troli <span>{{ collect(session('polimart_cart', []))->sum() }}</span></a>
                    <a class="public-nav-button" href="{{ route('login') }}">Log Masuk</a>
                </nav>
                @include('partials.theme-switcher')
            </div>
        </header>

        <main class="public-storefront-content">
            @if(session('status'))<div class="public-container"><div class="alert alert-success auto-dismiss-alert">{{ session('status') }}</div></div>@endif
            @if($errors->any())<div class="public-container"><div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>@endif
            @yield('content')
        </main>

        @include('partials.site-footer')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
