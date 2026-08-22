@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
@php
    $user = auth()->user();
    $role = $user->role;
    $firstName = str($user->name)->before(' ');
    $latestPayment = $latestPayments->first();
    $pendingActionCount = match ($role) {
        'admin' => $pendingMembers + $pendingPayments,
        'treasurer' => $pendingPayments,
        'member' => ($user->fee_balance > 0 ? 1 : 0),
        default => 0,
    };

    $summaryCards = match ($role) {
        'admin' => [
            ['icon' => 'bi-people', 'label' => 'Ahli Aktif', 'value' => $activeMembers, 'meta' => 'Keahlian aktif dalam sistem', 'route' => route('admin.index'), 'link' => 'Urus pengguna'],
            ['icon' => 'bi-person-check', 'label' => 'Kelulusan Ahli', 'value' => $pendingMembers, 'meta' => 'Pendaftaran menunggu semakan', 'route' => route('admin.members.pending'), 'link' => 'Semak permohonan'],
            ['icon' => 'bi-wallet2', 'label' => 'Bayaran Tertunda', 'value' => $pendingPayments, 'meta' => 'Bukti belum disahkan', 'route' => route('payments.index'), 'link' => 'Semak bayaran'],
            ['icon' => 'bi-shield-check', 'label' => 'Aktiviti Audit', 'value' => $recentAuditLogs->count(), 'meta' => 'Rekod sistem terkini', 'route' => route('admin.audit'), 'link' => 'Lihat jejak audit'],
        ],
        'treasurer' => [
            ['icon' => 'bi-arrow-down-left', 'label' => 'Pendapatan', 'value' => 'RM '.number_format((float) $income, 2), 'meta' => 'Jumlah transaksi aktif', 'route' => route('reports.financial'), 'link' => 'Lihat laporan'],
            ['icon' => 'bi-arrow-up-right', 'label' => 'Perbelanjaan', 'value' => 'RM '.number_format((float) $expenses, 2), 'meta' => 'Jumlah transaksi aktif', 'route' => route('transactions.index'), 'link' => 'Lihat transaksi'],
            ['icon' => 'bi-wallet2', 'label' => 'Baki Semasa', 'value' => 'RM '.number_format((float) ($income - $expenses), 2), 'meta' => 'Pendapatan selepas belanja', 'route' => route('reports.financial'), 'link' => 'Lihat ringkasan'],
            ['icon' => 'bi-hourglass-split', 'label' => 'Perlu Disemak', 'value' => $pendingPayments, 'meta' => 'Bukti bayaran tertunda', 'route' => route('payments.index'), 'link' => 'Semak sekarang'],
        ],
        'chairman' => [
            ['icon' => 'bi-calendar3', 'label' => 'Jumlah Aktiviti', 'value' => $totalActivities, 'meta' => 'Aktiviti direkodkan', 'route' => route('activities.index'), 'link' => 'Lihat aktiviti'],
            ['icon' => 'bi-check-circle', 'label' => 'Aktiviti Diluluskan', 'value' => $approvedActivities, 'meta' => 'Sedia untuk penyertaan', 'route' => route('activities.index'), 'link' => 'Semak aktiviti'],
            ['icon' => 'bi-clipboard-check', 'label' => 'Kehadiran', 'value' => $totalAttendances, 'meta' => 'Jumlah rekod kehadiran', 'route' => route('attendance.index'), 'link' => 'Lihat laporan'],
            ['icon' => 'bi-chat-left-text', 'label' => 'Maklum Balas', 'value' => $totalFeedbacks, 'meta' => 'Respons diterima', 'route' => route('admin.feedback.index'), 'link' => 'Semak respons'],
        ],
        default => [
            ['icon' => 'bi-calendar3', 'label' => 'Aktiviti Akan Datang', 'value' => $upcomingActivities->count(), 'meta' => 'Dalam jadual kelab', 'route' => route('activities.index'), 'link' => 'Lihat semua'],
            ['icon' => 'bi-person-check', 'label' => 'Status Keahlian', 'value' => ucfirst($user->membership_status), 'meta' => 'Status akaun semasa', 'route' => route('profile.show'), 'link' => 'Lihat profil'],
            ['icon' => 'bi-wallet2', 'label' => 'Status Bayaran', 'value' => $latestPayment ? ucfirst($latestPayment->status) : 'Belum dihantar', 'meta' => 'Bayaran yuran terkini', 'route' => route('payments.index'), 'link' => 'Lihat bayaran'],
            ['icon' => 'bi-bell', 'label' => 'Notifikasi', 'value' => $notifications->count(), 'meta' => 'Makluman terkini', 'route' => route('notifications.index'), 'link' => 'Lihat notifikasi'],
        ],
    };
@endphp

<section class="page-intro">
    <h1>Selamat datang, {{ $firstName }}</h1>
    <p>
        @if($pendingActionCount > 0)
            Anda mempunyai {{ $pendingActionCount }} tindakan yang memerlukan perhatian.
        @else
            Semua urusan utama anda telah dikemas kini.
        @endif
    </p>
</section>

<section aria-label="Ringkasan utama" class="mb-5">
    <div class="row g-4">
        @foreach($summaryCards as $summary)
            <div class="col-sm-6 col-xl-3">
                <article class="card stat-card">
                    <div class="card-body">
                        <div class="stat-card-header">
                            <span class="stat-icon"><i class="bi {{ $summary['icon'] }}" aria-hidden="true"></i></span>
                            <div>
                                <div class="stat-label">{{ $summary['label'] }}</div>
                                <div class="stat-value">{{ $summary['value'] }}</div>
                            </div>
                        </div>
                        <div class="stat-meta">{{ $summary['meta'] }}</div>
                        <a class="stat-link mt-auto" href="{{ $summary['route'] }}">
                            {{ $summary['link'] }} <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</section>

<section aria-label="Maklumat dashboard">
    <div class="row g-4">
        <div class="col-xl-5">
            <article class="card h-100">
                <div class="card-body">
                    <h2 class="panel-title">
                        <span>Tindakan Diperlukan</span>
                        @if($pendingActionCount > 0)<span class="badge"><i class="bi bi-info-circle" aria-hidden="true"></i>{{ $pendingActionCount }}</span>@endif
                    </h2>

                    @if($role === 'member' && $user->fee_balance > 0)
                        <div class="action-item">
                            <span class="action-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
                            <div>
                                <div class="list-item-title">Bayaran yuran belum selesai</div>
                                <div class="list-item-meta">Baki semasa anda ialah RM {{ number_format((float) $user->fee_balance, 2) }}.</div>
                            </div>
                            <a class="btn btn-primary align-self-center" href="{{ route('payments.create') }}">Bayar Yuran</a>
                        </div>
                    @elseif($role === 'treasurer' && $pendingPayments > 0)
                        <div class="action-item">
                            <span class="action-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                            <div>
                                <div class="list-item-title">{{ $pendingPayments }} bukti bayaran menunggu semakan</div>
                                <div class="list-item-meta">Sahkan rekod supaya resit dan baki yuran boleh dikemas kini.</div>
                            </div>
                            <a class="btn btn-primary align-self-center" href="{{ route('payments.index') }}">Semak Bayaran</a>
                        </div>
                    @elseif($role === 'admin' && $pendingMembers > 0)
                        <div class="action-item">
                            <span class="action-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>
                            <div>
                                <div class="list-item-title">{{ $pendingMembers }} permohonan ahli baharu</div>
                                <div class="list-item-meta">Semak maklumat pendaftaran sebelum memberi akses portal.</div>
                            </div>
                            <a class="btn btn-primary align-self-center" href="{{ route('admin.members.pending') }}">Semak Ahli</a>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <span class="stat-icon mx-auto mb-3"><i class="bi bi-check2" aria-hidden="true"></i></span>
                            <h3 class="h6">Tiada tindakan tertunda</h3>
                            <p class="text-muted small mb-0">Semua urusan utama telah dikemas kini.</p>
                        </div>
                    @endif

                    @if($role === 'member')
                        <div class="action-item">
                            <span class="action-icon"><i class="bi bi-qr-code-scan" aria-hidden="true"></i></span>
                            <div>
                                <div class="list-item-title">Rekod kehadiran aktiviti</div>
                                <div class="list-item-meta">Gunakan pengimbas QR apabila anda tiba di lokasi.</div>
                            </div>
                            <a class="btn btn-outline-primary align-self-center" href="{{ route('attendance.scan') }}">Imbas Kehadiran</a>
                        </div>
                    @endif
                </div>
            </article>
        </div>

        <div class="col-xl-4">
            <article class="card h-100">
                <div class="card-body">
                    <h2 class="panel-title">
                        <span>Aktiviti Akan Datang</span>
                        <a class="panel-link" href="{{ route('activities.index') }}">Lihat semua</a>
                    </h2>
                    @forelse($upcomingActivities as $activity)
                        <div class="list-item">
                            <span class="stat-icon date-tile">
                                <span class="text-center">
                                    <small class="date-tile-month">{{ mb_strtoupper($activity->date_time->format('M')) }}</small>
                                    <strong>{{ $activity->date_time->format('d') }}</strong>
                                </span>
                            </span>
                            <div>
                                <a class="list-item-title" href="{{ route('activities.show', $activity) }}">{{ $activity->title }}</a>
                                <div class="list-item-meta">
                                    <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ $activity->date_time->format('h:i A') }}
                                    <span class="d-block"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $activity->location }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <span class="stat-icon mx-auto mb-3"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                            <p class="text-muted mb-0">Tiada aktiviti akan datang.</p>
                        </div>
                    @endforelse
                </div>
            </article>
        </div>

        <div class="col-xl-3">
            <article class="card mb-4">
                <div class="card-body">
                    <h2 class="panel-title"><span>Status Bayaran</span></h2>
                    @if($latestPayment)
                        <div class="stat-label">Bayaran Terkini</div>
                        <div class="stat-value mt-1">RM {{ number_format((float) $latestPayment->amount, 2) }}</div>
                        <span class="badge mt-3"><i class="bi bi-info-circle" aria-hidden="true"></i>{{ ucfirst($latestPayment->status) }}</span>
                        <div class="stat-meta mt-3">{{ $latestPayment->payment_date->format('d/m/Y') }} · {{ $latestPayment->payment_method }}</div>
                    @else
                        <p class="text-muted">Belum ada bayaran dihantar.</p>
                    @endif
                    <a class="btn btn-primary w-100 mt-4" href="{{ route('payments.index') }}">Lihat Bayaran</a>
                </div>
            </article>

            <article class="card">
                <div class="card-body">
                    <h2 class="panel-title">
                        <span>Notifikasi Terkini</span>
                        <a class="panel-link" href="{{ route('notifications.index') }}">Semua</a>
                    </h2>
                    @forelse($notifications->take(3) as $notification)
                        <div class="list-item">
                            <i class="bi bi-bell list-item-icon" aria-hidden="true"></i>
                            <div>
                                <div class="list-item-title">{{ $notification->title }}</div>
                                <div class="list-item-meta">{{ str($notification->message)->limit(72) }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Tiada notifikasi baharu.</p>
                    @endforelse
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
