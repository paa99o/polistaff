@extends('layouts.app', ['title' => 'Admin System'])

@section('content')
<section class="admin-page">
    <div class="admin-hero">
        <div>
            <p class="stat-label mb-1">Pentadbiran Polistaff</p>
            <h1>Sistem Pentadbiran</h1>
            <p>Pantau kerja tertunda, yuran, ahli dan aktiviti kelab staff.</p>
        </div>
        <div class="admin-hero-actions no-print">
            <a class="btn btn-danger" href="{{ route('admin.members.pending') }}"><i class="bi bi-person-check me-2" aria-hidden="true"></i>Kelulusan Tertunda</a>
            <a class="btn btn-outline-danger" href="{{ route('settings.edit') }}"><i class="bi bi-gear me-2" aria-hidden="true"></i>Tetapan</a>
        </div>
    </div>

    <div class="admin-stat-grid">
        <a class="admin-stat-card" href="{{ route('admin.index', ['status' => 'active']) }}">
            <span class="admin-stat-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
            <span class="stat-label">Ahli Aktif</span>
            <strong>{{ $activeUsers }}</strong>
            <span>Daripada {{ $totalUsers }} pengguna</span>
        </a>
        <a class="admin-stat-card" href="{{ route('admin.members.pending') }}">
            <span class="admin-stat-icon admin-stat-icon-warning"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
            <span class="stat-label">Permohonan Tertunda</span>
            <strong>{{ $pendingUsers }}</strong>
            <span>Menunggu kelulusan admin</span>
        </a>
        <a class="admin-stat-card" href="{{ route('payments.index', ['status' => 'pending']) }}">
            <span class="admin-stat-icon admin-stat-icon-danger"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
            <span class="stat-label">Bayaran Tertunda</span>
            <strong>{{ $pendingPayments }}</strong>
            <span>Bukti bayaran perlu disemak</span>
        </a>
        <a class="admin-stat-card" href="{{ route('payments.statement') }}">
            <span class="admin-stat-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
            <span class="stat-label">Tunggakan Yuran</span>
            <strong>RM {{ number_format((float) $outstandingFees, 2) }}</strong>
            <span>Jumlah baki ahli aktif</span>
        </a>
        <a class="admin-stat-card" href="{{ route('admin.index', ['profile' => 'incomplete']) }}">
            <span class="admin-stat-icon admin-stat-icon-warning"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
            <span class="stat-label">Profil Belum Lengkap</span>
            <strong>{{ $incompleteProfiles }}</strong>
            <span>Perlu dilengkapkan oleh pengguna</span>
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <article class="card admin-panel h-100">
                <div class="card-body">
                    <div class="admin-panel-header">
                        <div>
                            <p class="stat-label mb-1">Penghantaran automatik</p>
                            <h2 class="h5 mb-0">Status Queue Emel</h2>
                        </div>
                        <span class="status-badge {{ $failedJobs > 0 ? 'status-warning' : 'status-success' }}">
                            <i class="bi {{ $failedJobs > 0 ? 'bi-exclamation-triangle' : 'bi-check-circle' }} me-1" aria-hidden="true"></i>
                            {{ $failedJobs > 0 ? 'Perlu perhatian' : 'Normal' }}
                        </span>
                    </div>
                    <div class="admin-mini-grid admin-queue-summary">
                        <div><strong>{{ $queuedJobs }}</strong><span>Menunggu worker</span></div>
                        <div><strong>{{ $failedJobs }}</strong><span>Job gagal</span></div>
                    </div>
                    @if($failedJobs > 0)
                        <form method="POST" action="{{ route('admin.queue.retry-failed') }}" class="mt-3 no-print">
                            @csrf
                            <button class="btn btn-outline-danger" type="submit" data-confirm="Cuba semula semua job gagal sekarang?">
                                <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i>Cuba Semula Emel Gagal
                            </button>
                        </form>
                    @else
                        <p class="text-muted mb-0 mt-3">Tiada emel gagal menunggu tindakan.</p>
                    @endif
                </div>
            </article>
        </div>
        <div class="col-lg-4">
            <article class="card admin-panel h-100">
                <div class="card-body">
                    <p class="stat-label mb-1">Nota operasi</p>
                    <h2 class="h5">Worker emel</h2>
                    <p class="text-muted mb-0">Pastikan <code>php artisan queue:work</code> berjalan supaya emel yang menunggu dihantar.</p>
                </div>
            </article>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <article class="card admin-panel h-100">
                <div class="card-body">
                    <div class="admin-panel-header">
                        <div>
                            <p class="stat-label mb-1">Pusat Tindakan</p>
                            <h2 class="h5 mb-0">Kerja Perlu Tindakan</h2>
                        </div>
                        <a class="panel-link" href="{{ route('admin.audit') }}">Audit Trail</a>
                    </div>

                    <div class="admin-action-grid">
                        <a class="admin-action-card" href="{{ route('activities.index') }}">
                            <span class="admin-action-icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
                            <span>
                                <strong>{{ $pendingActivities }} aktiviti menunggu kelulusan</strong>
                                <small>Semak cadangan aktiviti sebelum dibuka kepada ahli.</small>
                            </span>
                        </a>
                        <a class="admin-action-card" href="{{ route('claims.index') }}">
                            <span class="admin-action-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                            <span>
                                <strong>{{ $pendingClaims }} tuntutan perlu semakan</strong>
                                <small>Tuntutan tertunda atau telah disahkan bendahari.</small>
                            </span>
                        </a>
                        <a class="admin-action-card" href="{{ route('settings.edit') }}">
                            <span class="admin-action-icon"><i class="bi bi-calendar2-plus" aria-hidden="true"></i></span>
                            <span>
                                <strong>Jana yuran dan peringatan</strong>
                                <small>Cipta bil bulanan dan hantar peringatan kepada ahli tertunggak.</small>
                            </span>
                        </a>
                        <a class="admin-action-card" href="{{ route('notifications.create') }}">
                            <span class="admin-action-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
                            <span>
                                <strong>Hantar hebahan bersasar</strong>
                                <small>Sasarkan semua ahli aktif, jabatan, peranan atau individu.</small>
                            </span>
                        </a>
                        <a class="admin-action-card" href="{{ route('admin.polimart.reports') }}">
                            <span class="admin-action-icon"><i class="bi bi-flag" aria-hidden="true"></i></span>
                            <span>
                                <strong>{{ $pendingPolimartReports }} report PoliMart menunggu semakan</strong>
                                <small>Semak listing yang dilaporkan oleh komuniti staf.</small>
                            </span>
                        </a>
                    </div>

                    <div class="admin-quick-actions no-print">
                        <a class="btn btn-outline-danger" href="{{ route('activities.create') }}"><i class="bi bi-calendar-plus me-2" aria-hidden="true"></i>Tambah Aktiviti</a>
                        <a class="btn btn-outline-danger" href="{{ route('payments.index', ['status' => 'pending']) }}"><i class="bi bi-receipt me-2" aria-hidden="true"></i>Semak Bayaran</a>
                        <a class="btn btn-outline-danger" href="{{ route('notifications.create') }}"><i class="bi bi-bell me-2" aria-hidden="true"></i>Hantar Notifikasi</a>
                        <a class="btn btn-outline-danger" href="{{ route('reports.overview') }}"><i class="bi bi-bar-chart me-2" aria-hidden="true"></i>Laporan</a>
                    </div>
                </div>
            </article>
        </div>

        <div class="col-lg-4">
            <article class="card admin-panel h-100">
                <div class="card-body">
                    <div class="admin-panel-header">
                        <h2 class="h5 mb-0">Aktiviti Akan Datang</h2>
                    </div>
                    <div class="admin-upcoming-list">
                        @forelse($upcomingActivities as $activity)
                            <a class="admin-upcoming-item" href="{{ route('activities.show', $activity) }}">
                                <span class="admin-date-badge">
                                    <strong>{{ $activity->date_time->format('d') }}</strong>
                                    <small>{{ $activity->date_time->format('M') }}</small>
                                </span>
                                <span>
                                    <strong>{{ $activity->title }}</strong>
                                    <small><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $activity->location }}</small>
                                    <small>{{ $activity->date_time->format('h:i A') }}</small>
                                </span>
                            </a>
                        @empty
                            <div class="dashboard-empty-state">
                                <span class="stat-icon"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                                <h3 class="h6">Tiada aktiviti</h3>
                                <p class="text-muted mb-0">Aktiviti akan datang akan dipaparkan di sini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <article class="card admin-panel h-100">
                <div class="card-body">
                    <h2 class="h5 soft-panel-title">Ringkasan Sistem</h2>
                    <div class="admin-mini-grid">
                        <div><strong>{{ $totalActivities }}</strong><span>Aktiviti</span></div>
                        <div><strong>{{ $totalAttendances }}</strong><span>Kehadiran</span></div>
                        <div><strong>{{ $users->total() }}</strong><span>Senarai Ditapis</span></div>
                        <div><strong>RM {{ number_format((float) $netBalance, 2) }}</strong><span>Baki Bersih</span></div>
                    </div>
                </div>
            </article>
        </div>
        <div class="col-lg-5">
            <article class="card admin-panel h-100">
                <div class="card-body">
                    <h2 class="h5 soft-panel-title">Jejak Audit Terkini</h2>
                    <div class="admin-audit-feed">
                        @forelse($recentAuditLogs as $log)
                            <div class="admin-audit-item">
                                <span></span>
                                <div>
                                    <strong>{{ $log->module }}</strong>
                                    <p>{{ $log->description }}</p>
                                    <small>{{ $log->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Belum ada audit log.</p>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>
    </div>

    <article class="card admin-panel">
        <div class="card-body">
            <div class="admin-panel-header">
                <div>
                    <p class="stat-label mb-1">Direktori Pengguna</p>
                    <h2 class="h5 mb-0">Pengurusan Pengguna</h2>
                </div>
            </div>

            <form class="admin-user-filter no-print">
                <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama, emel atau jabatan">
                <select class="form-select" name="role">
                    <option value="">Semua Role</option>
                    @foreach(['member','treasurer','chairman','admin'] as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ \App\Support\PolistaffLabels::role($role) }}</option>
                    @endforeach
                </select>
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    @foreach(['pending','active','inactive'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Support\PolistaffLabels::status($status) }}</option>
                    @endforeach
                </select>
                <select class="form-select" name="profile">
                    <option value="">Semua Profil</option>
                    <option value="complete" @selected(request('profile') === 'complete')>Profil Lengkap</option>
                    <option value="incomplete" @selected(request('profile') === 'incomplete')>Belum Lengkap</option>
                </select>
                <button class="btn btn-danger">Tapis</button>
            </form>

            <div class="table-responsive">
                <table class="table mobile-records align-middle admin-user-table mb-0">
                    <thead><tr><th>Ahli</th><th>Jabatan</th><th>Peranan</th><th>Status</th><th>Baki Yuran</th><th></th></tr></thead>
                    <tbody>
                    @forelse($users as $user)
                        @php
                            $userInitials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($name) => mb_strtoupper(mb_substr($name, 0, 1)))->implode('');
                            $statusClass = \App\Support\PolistaffLabels::statusClass($user->membership_status);
                            $profileComplete = $user->profileIsComplete();
                        @endphp
                        <tr>
                            <td data-label="Ahli">
                                <div class="user-list-profile">
                                    <span class="user-list-avatar">
                                        @if($user->profile_photo_path)
                                            <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" alt="Gambar profil {{ $user->name }}">
                                        @else
                                            {{ $userInitials ?: 'PS' }}
                                        @endif
                                    </span>
                                    <span>
                                        <strong>{{ $user->name }}</strong>
                                        <span class="small text-muted d-block">{{ $user->email }}</span>
                                        <span class="badge {{ $profileComplete ? 'text-bg-success' : 'text-bg-warning' }} mt-1">{{ $profileComplete ? 'Profil lengkap' : 'Belum lengkap' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Jabatan">{{ $user->department ?? '-' }}</td>
                            <td data-label="Peranan"><span class="badge text-bg-light">{{ \App\Support\PolistaffLabels::role($user->role) }}</span></td>
                            <td data-label="Status"><span class="badge {{ $statusClass }}">{{ \App\Support\PolistaffLabels::status($user->membership_status) }}</span></td>
                            <td data-label="Baki Yuran">RM {{ number_format((float) $user->fee_balance, 2) }}</td>
                            <td data-label="Tindakan">
                                <form method="post" action="{{ route('admin.users.update', $user) }}" class="admin-user-update" data-confirm="Kemaskini peranan, status atau baki yuran untuk {{ $user->name }}?">
                                    @csrf
                                    @method('patch')
                                    <select class="form-select form-select-sm" name="role" aria-label="Role {{ $user->name }}">
                                        @foreach(['member','treasurer','chairman','admin'] as $role)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ \App\Support\PolistaffLabels::role($role) }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select form-select-sm" name="membership_status" aria-label="Status {{ $user->name }}">
                                        @foreach(['pending','active','inactive'] as $status)
                                            <option value="{{ $status }}" @selected($user->membership_status === $status)>{{ \App\Support\PolistaffLabels::status($status) }}</option>
                                        @endforeach
                                    </select>
                                    <input class="form-control form-control-sm" type="number" step="0.01" name="fee_balance" value="{{ $user->fee_balance }}" aria-label="Baki yuran {{ $user->name }}">
                                    <button class="btn btn-sm btn-danger">Kemaskini</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Tiada pengguna dijumpai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <div class="mt-3">{{ $users->links() }}</div>
</section>
@endsection
