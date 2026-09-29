@extends('layouts.app', ['title' => 'Admin System'])

@section('content')
<section class="admin-page">
    <div class="admin-hero">
        <div>
            <p class="stat-label mb-1">Pentadbiran POLIBEST</p>
            <h1>Sistem Pentadbiran</h1>
            <p>Pantau kerja tertunda, yuran, ahli dan aktiviti kelab staff.</p>
        </div>
        <div class="admin-hero-actions no-print">
            <a class="btn btn-primary" href="{{ route('admin.users') }}"><i class="bi bi-people me-2" aria-hidden="true"></i>Urus Pengguna</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.members.pending') }}"><i class="bi bi-person-check me-2" aria-hidden="true"></i>Kelulusan Ahli</a>
        </div>
    </div>

    <div class="admin-stat-grid">
        <a class="admin-stat-card" href="{{ route('admin.users', ['status' => 'active']) }}">
            <span class="admin-stat-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
            <span class="stat-label">Ahli Aktif</span>
            <strong>{{ $activeUsers }}</strong>
            <span>Daripada {{ $totalUsers }} pengguna</span>
        </a>
        <a class="admin-stat-card" href="{{ route('admin.users', ['status' => 'pending']) }}">
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
        <a class="admin-stat-card" href="{{ route('admin.users', ['profile' => 'incomplete']) }}">
            <span class="admin-stat-icon admin-stat-icon-warning"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
            <span class="stat-label">Profil Belum Lengkap</span>
            <strong>{{ $incompleteProfiles }}</strong>
            <span>Perlu dilengkapkan oleh pengguna</span>
        </a>
    </div>

    <article class="card admin-panel admin-mail-status-panel mb-4">
        <div class="card-body">
            <div>
                <p class="stat-label mb-1">Penghantaran automatik</p>
                <h2 class="h5 mb-1">Status emel</h2>
                <p class="text-muted mb-0">
                    @if($failedJobs > 0)
                        {{ $failedJobs }} emel gagal{{ $queuedJobs > 0 ? ' · '.$queuedJobs.' menunggu penghantaran' : '' }}.
                    @elseif($queuedJobs > 0)
                        {{ $queuedJobs }} emel menunggu penghantaran.
                    @else
                        Tiada emel tertunda atau gagal.
                    @endif
                </p>
            </div>
            <div class="admin-mail-status-actions">
                <span class="status-badge {{ $failedJobs > 0 ? 'status-warning' : 'status-success' }}">
                    <i class="bi {{ $failedJobs > 0 ? 'bi-exclamation-triangle' : 'bi-check-circle' }} me-1" aria-hidden="true"></i>
                    {{ $failedJobs > 0 ? 'Perlu perhatian' : 'Normal' }}
                </span>
                <a class="panel-link" href="{{ route('admin.notifications.delivery') }}">Lihat status emel</a>
                @if($failedJobs > 0)
                    <form method="POST" action="{{ route('admin.queue.retry-failed') }}" class="no-print">
                        @csrf
                        <button class="btn btn-outline-primary btn-sm" type="submit" data-confirm="Cuba semula semua job gagal sekarang?">
                            <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Cuba semula
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </article>

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
                        @if($pendingActivities > 0)
                            <a class="admin-action-card" href="{{ route('activities.index') }}">
                                <span class="admin-action-icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
                                <span>
                                    <strong>{{ $pendingActivities }} aktiviti menunggu kelulusan</strong>
                                    <small>Semak cadangan aktiviti sebelum dibuka kepada ahli.</small>
                                </span>
                            </a>
                        @endif
                        @if($pendingClaims > 0)
                            <a class="admin-action-card" href="{{ route('claims.index') }}">
                                <span class="admin-action-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                                <span>
                                    <strong>{{ $pendingClaims }} tuntutan perlu semakan</strong>
                                    <small>Tuntutan tertunda atau telah disahkan bendahari.</small>
                                </span>
                            </a>
                        @endif
                        @if($pendingPolimartReports > 0)
                            <a class="admin-action-card" href="{{ route('admin.polimart.reports') }}">
                                <span class="admin-action-icon"><i class="bi bi-flag" aria-hidden="true"></i></span>
                                <span>
                                    <strong>{{ $pendingPolimartReports }} laporan PoliMart menunggu semakan</strong>
                                    <small>Semak listing yang dilaporkan oleh komuniti staf.</small>
                                </span>
                            </a>
                        @endif
                        @if($pendingUsers > 0)
                            <a class="admin-action-card" href="{{ route('admin.users', ['status' => 'pending']) }}">
                                <span class="admin-action-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>
                                <span>
                                    <strong>{{ $pendingUsers }} permohonan ahli menunggu</strong>
                                    <small>Semak dan urus permohonan ahli baharu.</small>
                                </span>
                            </a>
                        @endif
                        @if($pendingPayments > 0)
                            <a class="admin-action-card" href="{{ route('payments.index', ['status' => 'pending']) }}">
                                <span class="admin-action-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
                                <span>
                                    <strong>{{ $pendingPayments }} bayaran perlu semakan</strong>
                                    <small>Sahkan bukti bayaran yuran yang dihantar.</small>
                                </span>
                            </a>
                        @endif
                        @if($incompleteProfiles > 0)
                            <a class="admin-action-card" href="{{ route('admin.users', ['profile' => 'incomplete']) }}">
                                <span class="admin-action-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
                                <span>
                                    <strong>{{ $incompleteProfiles }} profil belum lengkap</strong>
                                    <small>Lihat ahli yang perlu melengkapkan profil.</small>
                                </span>
                            </a>
                        @endif
                    </div>

                    @if($pendingActivities === 0 && $pendingClaims === 0 && $pendingPolimartReports === 0 && $pendingUsers === 0 && $pendingPayments === 0 && $incompleteProfiles === 0)
                        <div class="dashboard-empty-state admin-actions-empty">
                            <span class="stat-icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
                            <h3 class="h6">Tiada tindakan tertunda</h3>
                            <p class="text-muted mb-0">Semua urusan utama telah dikemas kini.</p>
                        </div>
                    @endif
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

    <article class="card admin-panel mb-4">
        <div class="card-body">
            <div class="admin-panel-header">
                <div>
                    <p class="stat-label mb-1">Rekod terkini</p>
                    <h2 class="h5 mb-0">Jejak Audit Terkini</h2>
                </div>
                <a class="panel-link" href="{{ route('admin.audit') }}">Lihat semua rekod</a>
            </div>
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
</section>
@endsection
