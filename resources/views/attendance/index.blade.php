@extends('layouts.app', ['title' => 'Laporan Kehadiran'])
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="stat-label mb-1">Report</p>
        <h1 class="h3 mb-0">Laporan Kehadiran</h1>
    </div>
    <div class="no-print d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-danger" href="{{ route('reports.attendance.csv') }}">CSV</a>
        <button onclick="print()" class="btn btn-outline-secondary">Cetak</button>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2">
            <div class="col-md-4">
                <input class="form-control" name="member" value="{{ request('member') }}" placeholder="Nama ahli">
            </div>
            <div class="col-md-4">
                <input class="form-control" name="activity" value="{{ request('activity') }}" placeholder="Tajuk aktiviti">
            </div>
            <div class="col-md-2">
                <input class="form-control" type="date" name="date" value="{{ request('date') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-danger w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mobile-records mb-0">
            <thead>
                <tr>
                    <th>Ahli</th>
                    <th>Aktiviti</th>
                    <th>Masa</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td data-label="Ahli">{{ $attendance->user->name }}</td>
                        <td data-label="Aktiviti">{{ $attendance->activity->title }}</td>
                        <td data-label="Masa">{{ $attendance->scanned_at->format('d/m/Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-muted">Tiada rekod kehadiran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $attendances->links() }}</div>
@endsection
