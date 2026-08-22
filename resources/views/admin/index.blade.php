@extends('layouts.app', ['title' => 'Admin System'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Admin System</h1>
        <p class="text-muted mb-0">Kawal ahli, peranan, status keahlian dan baki yuran.</p>
    </div>
    <a class="btn btn-outline-danger" href="{{ route('admin.members.pending') }}">Pending Approval</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="stat-label">Jumlah Ahli</div><div class="stat-value">{{ $totalUsers }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="stat-label">Aktif</div><div class="stat-value">{{ $activeUsers }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="stat-label">Pending</div><div class="stat-value">{{ $pendingUsers }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="stat-label">Pending Payment</div><div class="stat-value">{{ $pendingPayments }}</div></div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card"><div class="card-body"><h2 class="h5 soft-panel-title">Ringkasan Sistem</h2><div class="row text-center mt-3"><div class="col"><div class="h4 mb-0">{{ $totalActivities }}</div><div class="text-muted small">Aktiviti</div></div><div class="col"><div class="h4 mb-0">{{ $totalAttendances }}</div><div class="text-muted small">Kehadiran</div></div><div class="col"><div class="h4 mb-0">{{ $users->total() }}</div><div class="text-muted small">Senarai Ditapis</div></div></div></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-body"><h2 class="h5 soft-panel-title">Quick Actions</h2><div class="d-flex flex-wrap gap-2 mt-3"><a class="btn btn-outline-danger" href="{{ route('activities.create') }}">Tambah Aktiviti</a><a class="btn btn-outline-danger" href="{{ route('payments.index') }}">Review Bayaran</a><a class="btn btn-outline-danger" href="{{ route('notifications.create') }}">Hantar Notifikasi</a><a class="btn btn-outline-danger" href="{{ route('reports.financial') }}">Laporan Kewangan</a><a class="btn btn-outline-danger" href="{{ route('admin.audit') }}">Audit Trail</a></div></div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 soft-panel-title">Manual Process Replaced</h2>
                <div class="row g-3 mt-2">
                    <div class="col-md-6"><div class="technical-summary"><strong>Borang kertas ahli</strong><div class="text-muted small">Diganti dengan pendaftaran online dan kelulusan admin.</div></div></div>
                    <div class="col-md-6"><div class="technical-summary"><strong>Buku lejar manual</strong><div class="text-muted small">Diganti dengan transaksi digital, auto receipt dan laporan PDF.</div></div></div>
                    <div class="col-md-6"><div class="technical-summary"><strong>Tandatangan kehadiran</strong><div class="text-muted small">Diganti dengan QR attendance untuk elak nama tertinggal.</div></div></div>
                    <div class="col-md-6"><div class="technical-summary"><strong>Memo/WhatsApp bertindih</strong><div class="text-muted small">Diganti dengan notifikasi berpusat dalam sistem.</div></div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 soft-panel-title">Recent Audit Trail</h2>
                @forelse($recentAuditLogs as $log)
                    <div class="border-bottom py-2"><strong>{{ $log->module }}</strong><div class="small text-muted">{{ $log->description }} · {{ $log->created_at->diffForHumans() }}</div></div>
                @empty
                    <p class="text-muted mb-0">Belum ada audit log.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 soft-panel-title mb-0">Pengurusan Pengguna</h2>
        </div>
        <form class="row g-2 mb-3">
            <div class="col-md-5"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama, emel atau jabatan"></div>
            <div class="col-md-2"><select class="form-select" name="role"><option value="">Semua Role</option>@foreach(['member','treasurer','chairman','admin'] as $role)<option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="status"><option value="">Semua Status</option>@foreach(['pending','active','inactive'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
            <div class="col-md-3"><button class="btn btn-danger w-100">Tapis</button></div>
        </form>
        <div class="table-responsive">
            <table class="table mobile-records align-middle mb-0">
                <thead><tr><th>Ahli</th><th>Jabatan</th><th>Role</th><th>Status</th><th>Baki Yuran</th><th></th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td data-label="Ahli"><strong>{{ $user->name }}</strong><div class="small text-muted">{{ $user->email }}</div></td>
                        <td data-label="Jabatan">{{ $user->department ?? '-' }}</td>
                        <td data-label="Tindakan" colspan="4">
                            <form method="post" action="{{ route('admin.users.update', $user) }}" class="row g-2 align-items-center">
                                @csrf @method('patch')
                                <div class="col-md-3"><select class="form-select form-select-sm" name="role">@foreach(['member','treasurer','chairman','admin'] as $role)<option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>@endforeach</select></div>
                                <div class="col-md-3"><select class="form-select form-select-sm" name="membership_status">@foreach(['pending','active','inactive'] as $status)<option value="{{ $status }}" @selected($user->membership_status === $status)>{{ $status }}</option>@endforeach</select></div>
                                <div class="col-md-3"><input class="form-control form-control-sm" type="number" step="0.01" name="fee_balance" value="{{ $user->fee_balance }}"></div>
                                <div class="col-md-3"><button class="btn btn-sm btn-danger w-100">Update</button></div>
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
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
