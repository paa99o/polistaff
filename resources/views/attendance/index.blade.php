@extends('layouts.app', ['title' => 'Laporan Kehadiran'])
@section('content')
<div class="d-flex justify-content-between align-items-center mt-4">
    <div>
        <p class="stat-label mt-1">Report</p>
        <h1 class="h3 mt-0">Laporan Kehadiran</h1>
    </div>
    <div class="no-print d-flex gap-2 flex-wrap">
        @include('reports._export-menu', ['options' => [
            ['label' => 'PDF', 'url' => route('reports.attendance.pdf', request()->query()), 'icon' => 'ti-file-earmark-pdf'],
            ['label' => 'CSV', 'url' => route('reports.attendance.csv', request()->query()), 'icon' => 'ti-filetype-csv'],
            ['label' => 'Cetak / Simpan PDF', 'onclick' => 'window.print()', 'icon' => 'ti-printer'],
        ]])
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        <form class="row g-2">
            <div class="col-md-4">
                <input class="form-control" name="member" value="{{ request('member') }}" placeholder="Nama ahli" aria-label="Nama ahli">
            </div>
            <div class="col-md-4">
                <input class="form-control" name="activity" value="{{ request('activity') }}" placeholder="Tajuk aktiviti" aria-label="Tajuk aktiviti">
            </div>
            <div class="col-md-2">
                <input class="form-control" type="date" name="date" value="{{ request('date') }}" aria-label="Tarikh kehadiran">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-danger w-100" type="submit">Tapis</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mobile-records mt-0">
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
