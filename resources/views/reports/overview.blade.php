@extends('layouts.app', ['title' => 'Laporan Ringkasan'])
@section('content')
@php
    $monthName = \Carbon\Carbon::create()->month($month)->format('F');
@endphp

<section class="report-overview">
    <div class="report-toolbar no-print">
        <div>
            <p class="stat-label mb-1">Polistaff Analytics</p>
            <h1 class="mb-0">Laporan Ringkasan</h1>
        </div>
        <div class="report-actions">
            <a class="btn btn-outline-danger" href="{{ route('reports.financial', ['year' => $year, 'month' => $month]) }}">Kewangan</a>
            <a class="btn btn-outline-danger" href="{{ route('attendance.index') }}">Kehadiran</a>
            <button onclick="print()" class="btn btn-outline-secondary">Cetak</button>
        </div>
    </div>

    <form class="report-filter no-print">
        <input class="form-control" type="number" name="year" value="{{ $year }}" aria-label="Tahun">
        <input class="form-control" type="number" name="month" min="1" max="12" value="{{ $month }}" aria-label="Bulan">
        <button class="btn btn-danger">Jana</button>
    </form>

    <div class="report-period">{{ $monthName }} {{ $year }}</div>

    <div class="report-metric-grid">
        <article class="report-metric">
            <div class="stat-label">Pendapatan</div>
            <strong>RM {{ number_format((float) $income, 2) }}</strong>
            <span>{{ $recentTransactions->where('type', 'income')->count() }} transaksi masuk</span>
        </article>
        <article class="report-metric">
            <div class="stat-label">Perbelanjaan</div>
            <strong>RM {{ number_format((float) $expenses, 2) }}</strong>
            <span>{{ $recentTransactions->where('type', 'expense')->count() }} transaksi keluar</span>
        </article>
        <article class="report-metric">
            <div class="stat-label">Baki Bulan Ini</div>
            <strong>RM {{ number_format((float) $balance, 2) }}</strong>
            <span>Pendapatan tolak perbelanjaan</span>
        </article>
        <article class="report-metric">
            <div class="stat-label">Tunggakan Yuran</div>
            <strong>RM {{ number_format((float) $outstandingFees, 2) }}</strong>
            <span>{{ $activeMembers }} ahli aktif</span>
        </article>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <article class="card report-panel h-100">
                <div class="card-body">
                    <div class="report-panel-header">
                        <div>
                            <p class="stat-label mb-1">Trend 6 Bulan</p>
                            <h2 class="h5 mb-0">Kutipan vs Belanja</h2>
                        </div>
                    </div>
                    <div class="report-chart">
                        @foreach($monthlyTrend as $item)
                            <div class="report-chart-column">
                                <div class="report-bars">
                                    <span
                                        class="report-bar report-bar-income"
                                        tabindex="0"
                                        style="height: {{ max(6, ($item['income'] / $highestTrendValue) * 100) }}%"
                                        data-chart-label="{{ $item['label'] }}"
                                        data-chart-type="Pendapatan"
                                        data-chart-value="RM {{ number_format($item['income'], 2) }}"
                                    ></span>
                                    <span
                                        class="report-bar report-bar-expense"
                                        tabindex="0"
                                        style="height: {{ max(6, ($item['expenses'] / $highestTrendValue) * 100) }}%"
                                        data-chart-label="{{ $item['label'] }}"
                                        data-chart-type="Perbelanjaan"
                                        data-chart-value="RM {{ number_format($item['expenses'], 2) }}"
                                    ></span>
                                </div>
                                <span>{{ $item['label'] }}</span>
                            </div>
                        @endforeach
                        <div class="report-chart-tooltip" role="status" aria-live="polite"></div>
                    </div>
                    <div class="report-legend">
                        <span><i class="legend-income"></i>Pendapatan</span>
                        <span><i class="legend-expense"></i>Perbelanjaan</span>
                    </div>
                </div>
            </article>
        </div>
        <div class="col-xl-4">
            <article class="card report-panel h-100">
                <div class="card-body">
                    <p class="stat-label mb-1">Kehadiran</p>
                    <h2 class="h5 mb-3">Aktiviti Bulan Ini</h2>
                    <div class="report-rate">
                        <strong>{{ $attendanceRate }}%</strong>
                        <span>{{ $attendanceTotal }} hadir daripada {{ $registeredTotal }} pendaftaran</span>
                    </div>
                    <div class="progress report-progress" role="progressbar" aria-valuenow="{{ $attendanceRate }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {{ $attendanceRate }}%"></div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-6"><div class="technical-summary"><div class="stat-value">{{ $activityCount }}</div><div class="stat-meta">Aktiviti</div></div></div>
                        <div class="col-6"><div class="technical-summary"><div class="stat-value">{{ $attendanceTotal }}</div><div class="stat-meta">Hadir</div></div></div>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <article class="card report-panel h-100">
                <div class="card-body">
                    <div class="report-panel-header">
                        <h2 class="h5 mb-0">Prestasi Aktiviti</h2>
                        <a class="panel-link" href="{{ route('activities.index') }}">Lihat Aktiviti</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table mobile-records mb-0">
                            <thead><tr><th>Aktiviti</th><th>Daftar</th><th>Hadir</th><th>Kadar</th></tr></thead>
                            <tbody>
                                @forelse($activities as $activity)
                                    @php
                                        $rate = $activity->active_registrations_count > 0 ? round(($activity->attendances_count / $activity->active_registrations_count) * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td data-label="Aktiviti"><a href="{{ route('activities.show', $activity) }}">{{ $activity->title }}</a></td>
                                        <td data-label="Daftar">{{ $activity->active_registrations_count }}</td>
                                        <td data-label="Hadir">{{ $activity->attendances_count }}</td>
                                        <td data-label="Kadar">{{ $rate }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">Tiada aktiviti untuk tempoh ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>
        <div class="col-xl-5">
            <article class="card report-panel h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Pecahan Jabatan</h2>
                    @forelse($departmentBreakdown as $department)
                        @php
                            $percentage = $activeMembers > 0 ? round(($department->total / $activeMembers) * 100) : 0;
                        @endphp
                        <div class="report-breakdown-item">
                            <div class="d-flex justify-content-between gap-3">
                                <strong>{{ $department->department }}</strong>
                                <span>{{ $department->total }} ahli</span>
                            </div>
                            <div class="progress report-progress" role="progressbar" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Tiada data ahli aktif.</p>
                    @endforelse
                </div>
            </article>
        </div>
    </div>

    <article class="card report-panel mt-4">
        <div class="card-body">
            <div class="report-panel-header">
                <h2 class="h5 mb-0">Transaksi Terkini</h2>
                <a class="panel-link" href="{{ route('reports.financial', ['year' => $year, 'month' => $month]) }}">Laporan Kewangan</a>
            </div>
            <div class="table-responsive">
                <table class="table mobile-records mb-0">
                    <thead><tr><th>Tarikh</th><th>Jenis</th><th>Kategori</th><th>Keterangan</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($recentTransactions as $transaction)
                            <tr>
                                <td data-label="Tarikh">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td data-label="Jenis"><span class="badge bg-secondary">{{ $transaction->type }}</span></td>
                                <td data-label="Kategori">{{ $transaction->category }}</td>
                                <td data-label="Keterangan">{{ $transaction->description }}</td>
                                <td data-label="Jumlah">RM {{ number_format((float) $transaction->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">Tiada transaksi untuk tempoh ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.report-chart').forEach((chart) => {
        const tooltip = chart.querySelector('.report-chart-tooltip');
        const bars = chart.querySelectorAll('.report-bar[data-chart-label]');

        const showTooltip = (bar) => {
            const barBox = bar.getBoundingClientRect();
            const chartBox = chart.getBoundingClientRect();
            const left = barBox.left - chartBox.left + (barBox.width / 2);
            const top = barBox.top - chartBox.top;

            tooltip.innerHTML = `
                <strong>${bar.dataset.chartLabel}</strong>
                <span>${bar.dataset.chartType}: ${bar.dataset.chartValue}</span>
            `;
            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${top}px`;
            tooltip.classList.toggle('is-below', top < 72);
            tooltip.classList.toggle('is-right', left < 100);
            tooltip.classList.toggle('is-left', left > chartBox.width - 100);
            tooltip.classList.add('is-visible');
        };

        bars.forEach((bar) => {
            bar.addEventListener('mouseenter', () => showTooltip(bar));
            bar.addEventListener('focus', () => showTooltip(bar));
            bar.addEventListener('mouseleave', () => tooltip.classList.remove('is-visible'));
            bar.addEventListener('blur', () => tooltip.classList.remove('is-visible'));
        });
    });
</script>
@endpush
