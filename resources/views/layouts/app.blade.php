<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="POLISTAFF &mdash; Portal Pengurusan Kelab Staf">
    <title>{{ $title ?? 'POLISTAFF' }} &middot; POLISTAFF</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <meta name="theme-color" content="#1557D8">
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
                    <span class="brand-mark" aria-hidden="true">PS</span>
                    <span>
                        <span class="brand-name">POLISTAFF</span>
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

                    <div class="mega-nav-item {{ request()->routeIs('payments.*') || request()->routeIs('claims.*') ? 'active' : '' }}">
                        <button class="mega-nav-title" type="button">Kewangan</button>
                        <div class="mega-menu">
                            <a class="mega-menu-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                <i class="bi bi-wallet2" aria-hidden="true"></i><span>Bayaran Yuran</span>
                            </a>
                            <a class="mega-menu-link {{ request()->routeIs('claims.*') ? 'active' : '' }}" href="{{ route('claims.index') }}">
                                <i class="bi bi-receipt" aria-hidden="true"></i><span>Tuntutan</span>
                            </a>
                        </div>
                    </div>

                    <div class="mega-nav-item {{ request()->routeIs('dashboard') || request()->routeIs('profile.*') || request()->routeIs('notifications.*') || request()->routeIs('documents.*') || request()->routeIs('feedback.*') || request()->routeIs('transactions.*') || request()->routeIs('reports.*') || request()->routeIs('attendance.index') ? 'active' : '' }}">
                        <button class="mega-nav-title" type="button">Pengurusan</button>
                        <div class="mega-menu mega-menu-wide">
                            <a class="mega-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="bi bi-grid" aria-hidden="true"></i><span>Dashboard</span>
                            </a>
                            <a class="mega-menu-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}">
                                <i class="bi bi-person" aria-hidden="true"></i><span>Profil Saya</span>
                            </a>
                            <a class="mega-menu-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                                <i class="bi bi-bell" aria-hidden="true"></i><span>Notifikasi</span>
                            </a>
                            <a class="mega-menu-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                                <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>Dokumen</span>
                            </a>
                            <a class="mega-menu-link {{ request()->routeIs('feedback.*') ? 'active' : '' }}" href="{{ route('feedback.create') }}">
                                <i class="bi bi-chat-left-text" aria-hidden="true"></i><span>Maklum Balas</span>
                            </a>
                            @if(auth()->user()->hasRole('treasurer','chairman','admin'))
                                <a class="mega-menu-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}" href="{{ route('transactions.index') }}">
                                    <i class="bi bi-arrow-left-right" aria-hidden="true"></i><span>Transaksi</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.financial') }}">
                                    <i class="bi bi-bar-chart" aria-hidden="true"></i><span>Laporan</span>
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
                                <a class="mega-menu-link {{ request()->routeIs('admin.feedback.*') ? 'active' : '' }}" href="{{ route('admin.feedback.index') }}">
                                    <i class="bi bi-inbox" aria-hidden="true"></i><span>Senarai Maklum Balas</span>
                                </a>
                                <a class="mega-menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}">
                                    <i class="bi bi-gear" aria-hidden="true"></i><span>Tetapan</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </nav>

                <div class="topbar-heading">
                    <div class="topbar-brandline">POLISTAFF</div>
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
                            <span class="profile-avatar">{{ $userInitials ?: 'PS' }}</span>
                            <span class="profile-label">{{ auth()->user()->name }}</span>
                            <i class="bi bi-chevron-down small" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.show') }}">Profil Saya</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Kemaskini Profil</a></li>
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
                    <div class="alert alert-success" role="status">
                        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>{{ session('status') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><strong>Sila semak input anda.</strong>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
@else
    <main class="auth-shell">
        <div class="container">
            @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger"><strong>Sila semak input anda.</strong></div>@endif
            @yield('content')
        </div>
    </main>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {});
    }
</script>
@stack('scripts')
</body>
</html>
