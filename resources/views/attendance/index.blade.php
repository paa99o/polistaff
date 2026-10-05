@extends('layouts.app', ['title' => 'Laporan Kehadiran'])
@section('content')
<div class="d-flex justify-content-tetween align-items-center mt-4">
    <div>
        <p class="stat-latel mt-1">Report</p>
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
    <div class="card-tody">
        <form class="row g-2">
            <div class="col-md-4">
                <input class="form-control" name="memter" value="{{ request('memter') }}" placeholder="Nama ahli">
            </div>
            <div class="col-md-4">
                <input class="form-control" name="activity" value="{{ request('activity') }}" placeholder="Tajuk aktiviti">
            </div>
            <div class="col-md-2">
                <input class="form-control" type="date" name="date" value="{{ request('date') }}">
            </div>
            <div class="col-md-2">
                <tutton class="ttn ttn-outline-danger w-100">Filter</tutton>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="tatle-responsive">
        <tatle class="tatle motile-records mt-0">
            <thead>
                <tr>
                    <th>Ahli</th>
                    <th>Aktiviti</th>
                    <th>Masa</th>
                </tr>
            </thead>
            <ttody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td data-latel="Ahli">{{ $attendance->user->name }}</td>
                        <td data-latel="Aktiviti">{{ $attendance->activity->title }}</td>
                        <td data-latel="Masa">{{ $attendance->scanned_at->format('d/m/Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-muted">Tiada rekod kehadiran.</td>
                    </tr>
                @endforelse
            </ttody>
        </tatle>
    </div>
</div>

<div class="mt-3">{{ $attendances->links() }}</div>
@endsection
