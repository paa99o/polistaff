<!doctype html>
<html lang="ms"
    data-theme-preference="{{ auth()->check() ? auth()->user()->theme_preference : 'light' }}"
    data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
    data-text-size="{{ auth()->check() ? auth()->user()->text_size_preference : 'normal' }}"
    @if(auth()->check() && auth()->user()->reduce_motion) data-reduce-motion="true" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="POLIBEST &mdash; Portal Pengurusan Kelab Staf">
    <title>{{ $title ?? 'POLIBEST' }} &middot; POLIBEST</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <meta name="theme-color" content="#1557D8">
    @include('partials.theme-loader')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@auth
    @php
        $userInitials = collect(explode(' ', auth()->user()->name))
            ->filter()
            ->take(2)
            ->map(fn ($name) => mb_strtoupper(mb_substr($name, 0, 1)))
            ->implode('');
        $unreadNotifications = auth()->user()
            ->portalNotifications()
            ->where('is_read', false)
            ->latest()
            ->limit(5)
            ->get();
    @endphp

    <div class="app-shell">
        <div class="main-panel">
            <header class="topbar app-navbar no-print">
                <a class="app-navbar-brand" href="{{ route('dashboard') }}">
                    @include('partials.brand-mark')
                    <span>
                        <span class="brand-name">POLIBEST</span>
                        <span class="brand-description">Portal Pengurusan Kelab Staf</span>
                    </span>
                </a>

                <nav class="mega-nav" aria-label="Navigasi utama">
                    <div class="mega-nav-item {{ request()->routeIs('activities.*') || request()->routeIs('attendance.scan') ? 'active' : '' }}">
                        <button class="mega-nav-title" type="button">Aktiviti</button>
                        <div class="mega-menu">
                            <a class="mega-menu-link {{ request()->routeIs('activities.*') ? 'active' : '' }}" href="{{ route('activities.index') }}">
                                <i class="bi bi-calendar3" aria-hidden="true"></i><span>Aktiviti</span>
                            </a>
                            <a class="mega-menu-link {{ request()->routeIs('attendance.scan') ? 'active' : '' }}" href="{{ route('attendance.scan') }}">
                                <i class="bi bi-qr-code-scan" aria-hidden="true"></i><span>Imbas Kehadiran</span>
                            </a>
                        </div>
                    </div>

                    <div class="mega-nav-item {{ request()->routeIs('payments.*') || request()->routeIs('claims.*') || request()->routeIs('donations.*') || request()->routeIs('transactions.*') || request()->routeIs('reports.financial') || request()->routeIs('finance.fees.*') ? 'active' : '' }}">
                        <button class="mega-nav-title" type="button">Kewangan</button>
                        <div class="mega-menu mega-menu-wide">
                            <a class="mega-menu-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                <i class="bi bi-wallet2" aria-hidden="true"></i><span>Bayaran Yuran</span>
                            </a>
                            <a class="mega-menu-link {{ request()->routeIs('claims.*') ? 'active' : '' }}" href="{{ route('claims.index') }}">
                                <i class="bi bi-receipt" aria-hidden="true"></i><span>Tuntutan</span>
                            </a>
                            <a class="mega-menu-link {{ request()->routeIs('donations.*') ? 'active' : '' }}" href="{{ route('donations.index') }}">
                                <i class="bi bi-heart" aria-hidden="true"></i><span>Sumbangan</span>
                            </a>
                            @if(auth()->user()->hasRole('treasurer','admin'))
                                <a class="mega-menu-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}" href="{{ route('transactions.index') }}">
                                    <i class="bi bi-arrow-left-right" aria-hidden="true"></i><span>Transaksi</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('reports.financial') ? 'active' : '' }}" href="{{ route('reports.financial') }}">
                                    <i class="bi bi-graph-up-arrow" aria-hidden="true"></i><span>Laporan Kewangan</span>
                                </a>
                            @endif
                            @if(auth()->user()->hasRole('admin', 'treasurer'))
                                <a class="mega-menu-link {{ request()->routeIs('finance.fees.*') ? 'active' : '' }}" href="{{ route('finance.fees.index') }}">
                                    <i class="bi bi-calendar2-check" aria-hidden="true"></i><span>Pengurusan Yuran</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="mega-nav-item {{ request()->routeIs('polimart.*') ? 'active' : '' }}">
                        <a class="mega-nav-title" href="{{ route('polimart.index') }}">
                            <i class="bi bi-bag-heart me-2" aria-hidden="true"></i><span>PoliMart</span>
                        </a>
                    </div>

                    <div class="mega-nav-item {{ request()->routeIs('profile.*') || request()->routeIs('preferences.*') || request()->routeIs('notifications.*') || request()->routeIs('reports.overview') || request()->routeIs('attendance.index') ? 'active' : '' }}">
                        <button class="mega-nav-title" type="button">Pengurusan</button>
                        <div class="mega-menu mega-menu-wide">
                            <a class="mega-menu-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}">
                                <i class="bi bi-person" aria-hidden="true"></i><span>Profil Saya</span>
                            </a>
                            <a class="mega-menu-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                                <i class="bi bi-bell" aria-hidden="true"></i><span>Notifikasi</span>
                            </a>
                            @if(auth()->user()->hasRole('treasurer','admin'))
                                <a class="mega-menu-link {{ request()->routeIs('reports.overview') ? 'active' : '' }}" href="{{ route('reports.overview') }}">
                                    <i class="bi bi-bar-chart" aria-hidden="true"></i><span>Laporan Ringkasan</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}" href="{{ route('attendance.index') }}">
                                    <i class="bi bi-clipboard-data" aria-hidden="true"></i><span>Laporan Kehadiran</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if(auth()->user()->hasRole('admin'))
                        <div class="mega-nav-item {{ request()->routeIs('admin.*') || request()->routeIs('settings.*') ? 'active' : '' }}">
                            <button class="mega-nav-title" type="button">Pentadbiran</button>
                            <div class="mega-menu mega-menu-wide">
                                <a class="mega-menu-link {{ request()->routeIs('admin.index') ? 'active' : '' }}" href="{{ route('admin.index') }}">
                                    <i class="bi bi-people" aria-hidden="true"></i><span>Pengguna</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}" href="{{ route('admin.members.pending') }}">
                                    <i class="bi bi-person-check" aria-hidden="true"></i><span>Kelulusan Ahli</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('admin.audit') ? 'active' : '' }}" href="{{ route('admin.audit') }}">
                                    <i class="bi bi-shield-check" aria-hidden="true"></i><span>Jejak Audit</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('admin.polimart.*') ? 'active' : '' }}" href="{{ route('admin.polimart.reports') }}">
                                    <i class="bi bi-flag" aria-hidden="true"></i><span>Report PoliMart</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('admin.polimart.orders') ? 'active' : '' }}" href="{{ route('admin.polimart.orders') }}">
                                    <i class="bi bi-bag-check" aria-hidden="true"></i><span>Pesanan PoliMart</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" href="{{ route('admin.notifications.delivery') }}">
                                    <i class="bi bi-envelope-check" aria-hidden="true"></i><span>Status Email</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}">
                                    <i class="bi bi-gear" aria-hidden="true"></i><span>Tetapan Sistem</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </nav>

                <div class="topbar-heading">
                    <div class="topbar-brandline">POLIBEST</div>
                    <h1 class="topbar-title">{{ $title ?? 'Dashboard' }}</h1>
                </div>

                <div class="topbar-actions">
                    @if(auth()->user()->membership_status === 'inactive')
                        <a class="icon-button membership-apply-button" href="{{ route('membership.apply') }}" aria-label="Mohon ahli kelab staf" title="Mohon ahli kelab staf">
                            <i class="bi bi-person-vcard fs-5" aria-hidden="true"></i>
                        </a>
                    @elseif(auth()->user()->membership_status === 'pending')
                        <a class="icon-button membership-apply-button is-pending" href="{{ route('membership.apply') }}" aria-label="Permohonan ahli sedang disemak" title="Permohonan ahli sedang disemak">
                            <i class="bi bi-hourglass-split fs-5" aria-hidden="true"></i>
                        </a>
                    @endif

                    <div class="dropdown">
                        <button class="icon-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                            <i class="bi bi-bell fs-5" aria-hidden="true"></i>
                            @if($unreadNotifications->isNotEmpty())
                                <span class="notification-count">{{ $unreadNotifications->count() }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2 notification-menu">
                            @forelse($unreadNotifications as $notification)
                                <a class="dropdown-item p-3" href="{{ $notification->link ?? route('notifications.index') }}">
                                    <strong class="d-block">{{ $notification->title }}</strong>
                                    <span class="small text-muted text-wrap">{{ $notification->message }}</span>
                                </a>
                            @empty
                                <span class="dropdown-item p-3 text-muted">Tiada notifikasi baharu.</span>
                            @endforelse
                            <a class="dropdown-item text-center small p-3" href="{{ route('notifications.index') }}">Lihat semua notifikasi</a>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="profile-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="profile-avatar">
                                @if(auth()->user()->profile_photo_path)
                                    <img src="{{ Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" alt="Gambar profil {{ auth()->user()->name }}">
                                @else
                                    {{ $userInitials ?: 'PS' }}
                                @endif
                            </span>
                            <span class="profile-label">{{ auth()->user()->name }}</span>
                            <i class="bi bi-chevron-down small" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.show') }}">Profil Saya</a></li>
                            <li><a class="dropdown-item" href="{{ route('preferences.edit') }}"><i class="bi bi-gear me-2" aria-hidden="true"></i>Tetapan Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Log Keluar</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <main class="content-wrap">
                @if(session('status'))
                    <div class="alert alert-success auto-dismiss-alert" role="status">
                        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>{{ session('status') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger auto-dismiss-alert" role="alert">
                        <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><strong>Sila semak input anda.</strong>
                    </div>
                @endif
                @yield('content')
            </main>
            @include('partials.site-footer')
        </div>
    </div>
@else
    <header class="public-nav auth-public-nav">
        <div class="public-container d-flex align-items-center justify-content-between w-100">
            <a class="public-brand" href="{{ url('/') }}">
                @include('partials.brand-mark')
                <span>
                    <span class="brand-name">POLIBEST</span>
                    <span class="brand-description">Portal Pengurusan Kelab Staf</span>
                </span>
            </a>
            <nav class="public-links" aria-label="Navigasi utama">
                <a href="{{ route('activities.index') }}">Aktiviti</a>
                <a href="{{ route('polimart.index') }}">PoliMart</a>
                <a class="public-nav-button" href="{{ url('/') }}">Halaman Utama</a>
            </nav>
            @include('partials.theme-switcher')
        </div>
    </header>
    <main class="auth-shell">
        <div class="container">
            @if(session('status'))<div class="alert alert-success auto-dismiss-alert">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger auto-dismiss-alert"><strong>Sila semak input anda.</strong></div>@endif
            @yield('content')
        </div>
    </main>
    @include('partials.site-footer')
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {});
    }

    document.querySelectorAll('.auto-dismiss-alert').forEach((alert) => {
        window.setTimeout(() => {
            alert.classList.add('is-dismissing');
            window.setTimeout(() => alert.remove(), 650);
        }, 3000);
    });
</script>
@stack('scripts')
</body>
</html>
