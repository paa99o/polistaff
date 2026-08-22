@extends('layouts.app', ['title' => 'Audit Trail'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Audit Trail</h1>
        <p class="text-muted mb-0">Jejak perubahan penting untuk kawalan dalaman dan mengurangkan human error.</p>
    </div>
    <a class="btn btn-outline-danger" href="{{ route('admin.index') }}">Admin System</a>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h5 soft-panel-title">Senarai Aktiviti Sistem</h2>
        <form class="row g-2 mt-3">
            <div class="col-md-4"><input class="form-control" name="module" value="{{ request('module') }}" placeholder="Filter module"></div>
            <div class="col-md-3"><input class="form-control" name="action" value="{{ request('action') }}" placeholder="Action"></div>
            <div class="col-md-3"><input class="form-control" type="date" name="date" value="{{ request('date') }}"></div>
            <div class="col-md-2"><button class="btn btn-danger w-100">Filter</button></div>
        </form>
        <div class="table-responsive mt-3">
            <table class="table mobile-records align-middle mb-0">
                <thead><tr><th>Masa</th><th>Pengguna</th><th>Modul</th><th>Action</th><th>Butiran</th><th>IP</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td data-label="Masa">{{ $log->created_at->format('d/m/Y h:i A') }}</td>
                        <td data-label="Pengguna">{{ $log->user->name ?? 'System' }}</td>
                        <td data-label="Modul"><span class="badge bg-secondary">{{ $log->module }}</span></td>
                        <td data-label="Action">{{ $log->action }}</td>
                        <td data-label="Butiran">{{ $log->description }}</td>
                        <td data-label="IP" class="text-muted small">{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada rekod audit.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
