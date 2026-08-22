<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="POLISTAFF — Portal Pengurusan Kelab Staf">
    <title>{{ $title ?? 'POLISTAFF' }} · POLISTAFF</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <meta name="theme-color" content="#3878F8">
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
        <button class="mobile-overlay no-print" type="button" data-sidebar-close aria-label="Tutup menu"></button>

        <aside class="sidebar no-print" aria-label="Navigasi utama">
            <div class="sidebar-brand">
                <a class="d-flex align-items-center gap-3 text-dark" href="{{ route('dashboard') }}">
                    <span class="brand-mark" aria-hidden="true">PS</span>
                    <span>
                        <span class="brand-name">POLISTAFF</span>
                        <span class="brand-description">Portal Pengurusan Kelab Staf</span>
                    </span>
                </a>
                <button class="sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <nav class="nav flex-column">
                <div class="sidebar-section">Gambaran Keseluruhan</div>
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-grid" aria-hidden="true"></i><span>Dashboard</span>
                </a>
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.show') }}">
                    <i class="bi bi-person" aria-hidden="true"></i><span>Profil Saya</span>
                </a>

                <div class="sidebar-section">Aktiviti</div>
                <a class="nav-link {{ request()->routeIs('activities.*') ? 'active' : '' }}" href="{{ route('activities.index') }}">
                    <i class="bi bi-calendar3" aria-hidden="true"></i><span>Aktiviti</span>
                </a>
                <a class="nav-link {{ request()->routeIs('attendance.scan') ? 'active' : '' }}" href="{{ route('attendance.scan') }}">
                    <i class="bi bi-qr-code-scan" aria-hidden="true"></i><span>Imbas Kehadiran</span>
                </a>

                <div class="sidebar-section">Kewangan</div>
                <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                    <i class="bi bi-wallet2" aria-hidden="true"></i><span>Bayaran Yuran</span>
                </a>
                <a class="nav-link {{ request()->routeIs('claims.*') ? 'active' : '' }}" href="{{ route('claims.index') }}">
                    <i class="bi bi-receipt" aria-hidden="true"></i><span>Tuntutan</span>
                </a>

                <div class="sidebar-section">Komunikasi</div>
                <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                    <i class="bi bi-bell" aria-hidden="true"></i><span>Notifikasi</span>
                </a>
                <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>Dokumen</span>
                </a>
                <a class="nav-link {{ request()->routeIs('feedback.*') ? 'active' : '' }}" href="{{ route('feedback.create') }}">
                    <i class="bi bi-chat-left-text" aria-hidden="true"></i><span>Maklum Balas</span>
                </a>

                @if(auth()->user()->hasRole('treasurer','chairman','admin'))
                    <div class="sidebar-section">Pengurusan</div>
                    <a class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}" href="{{ route('transactions.index') }}">
                        <i class="bi bi-arrow-left-right" aria-hidden="true"></i><span>Transaksi</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.financial') }}">
                        <i class="bi bi-bar-chart" aria-hidden="true"></i><span>Laporan</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}" href="{{ route('attendance.index') }}">
                        <i class="bi bi-clipboard-data" aria-hidden="true"></i><span>Laporan Kehadiran</span>
                    </a>
                @endif

                @if(auth()->user()->hasRole('admin'))
                    <div class="sidebar-section">Pentadbiran</div>
                    <a class="nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}" href="{{ route('admin.index') }}">
                        <i class="bi bi-people" aria-hidden="true"></i><span>Pengguna</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}" href="{{ route('admin.members.pending') }}">
                        <i class="bi bi-person-check" aria-hidden="true"></i><span>Kelulusan Ahli</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.audit') ? 'active' : '' }}" href="{{ route('admin.audit') }}">
                        <i class="bi bi-shield-check" aria-hidden="true"></i><span>Jejak Audit</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.feedback.*') ? 'active' : '' }}" href="{{ route('admin.feedback.index') }}">
                        <i class="bi bi-inbox" aria-hidden="true"></i><span>Senarai Maklum Balas</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}">
                        <i class="bi bi-gear" aria-hidden="true"></i><span>Tetapan</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="nav-link border-0 w-100" type="submit">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Log Keluar</span>
                    </button>
                </form>
                <div class="px-3 pt-3 d-flex flex-wrap gap-2">
                    <a class="brand-description" href="#">Privasi</a>
                    <a class="brand-description" href="#">Terma</a>
                    <a class="brand-description" href="mailto:admin@polistaff.local">Hubungi</a>
                </div>
                <div class="brand-description px-3 pt-2">© {{ date('Y') }} POLISTAFF</div>
            </div>
        </aside>

        <div class="main-panel">
            <header class="topbar no-print">
                <button class="mobile-menu-btn" type="button" data-sidebar-open aria-label="Buka menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <h1 class="topbar-title">{{ $title ?? 'Dashboard' }}</h1>

                <form class="topbar-search" role="search" action="{{ route('activities.index') }}" method="get">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="portal-search">Cari dalam portal</label>
                    <input class="form-control" id="portal-search" name="search" type="search" placeholder="Cari dalam portal…">
                </form>

                <div class="topbar-actions">
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
    document.querySelectorAll('[data-sidebar-open]').forEach((button) => {
        button.addEventListener('click', () => document.body.classList.add('sidebar-open'));
    });
    document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
        button.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
    });
    document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
        link.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
    });
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {});
    }
</script>
@stack('scripts')
</body>
</html>
