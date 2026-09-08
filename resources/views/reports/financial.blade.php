@extends('layouts.app', ['title' => 'Laporan Kewangan'])
@section('content')
@php
    $query = request()->query();
    $pointCount = max(1, $chartItems->count() - 1);
    $incomePoints = $chartItems->values()->map(function ($item, $index) use ($pointCount, $highestChartValue) {
        $x = 36 + (($index / $pointCount) * 548);
        $y = 204 - (($item['income'] / $highestChartValue) * 168);

        return round($x, 2).','.round($y, 2);
    })->implode(' ');
    $expensePoints = $chartItems->values()->map(function ($item, $index) use ($pointCount, $highestChartValue) {
        $x = 36 + (($index / $pointCount) * 548);
        $y = 204 - (($item['expenses'] / $highestChartValue) * 168);

        return round($x, 2).','.round($y, 2);
    })->implode(' ');
@endphp

<section class="report-overview">
    <div class="report-toolbar no-print">
        <div>
            <p class="stat-label mb-1">Finance Analytics</p>
            <h1 class="mb-0">Laporan Kewangan</h1>
        </div>
        <div class="report-actions">
            <a class="btn btn-outline-danger" href="{{ route('reports.overview', ['year' => $year, 'month' => $month]) }}">Ringkasan</a>
            <a class="btn btn-outline-danger" href="{{ route('reports.financial.pdf', $query) }}">PDF</a>
            <a class="btn btn-outline-danger" href="{{ route('reports.financial.csv', $query) }}">CSV</a>
            <button onclick="print()" class="btn btn-outline-secondary">Cetak</button>
        </div>
    </div>

    <form class="report-filter finance-filter no-print">
        <select class="form-select" name="mode" aria-label="Jenis laporan">
            <option value="daily" @selected($mode === 'daily')>Harian</option>
            <option value="monthly" @selected($mode === 'monthly')>Bulanan</option>
            <option value="annually" @selected($mode === 'annually')>Tahunan</option>
        </select>
        <input class="form-control" type="date" name="from" value="{{ $from->toDateString() }}" aria-label="Tarikh mula">
        <input class="form-control" type="date" name="to" value="{{ $to->toDateString() }}" aria-label="Tarikh akhir">
        <input class="form-control" type="number" name="year" value="{{ $year }}" aria-label="Tahun">
        <input class="form-control" type="number" name="month" min="1" max="12" value="{{ $month }}" aria-label="Bulan">
        <button class="btn btn-danger">Jana</button>
    </form>

    <div class="report-period">{{ $periodLabel }}</div>

    <div class="report-metric-grid finance-metric-grid">
        <article class="report-metric">
            <div class="stat-label">Pendapatan</div>
            <strong>RM {{ number_format((float) $income, 2) }}</strong>
            <span>Jumlah duit masuk</span>
        </article>
        <article class="report-metric">
            <div class="stat-label">Perbelanjaan</div>
            <strong>RM {{ number_format((float) $expenses, 2) }}</strong>
            <span>Jumlah duit keluar</span>
        </article>
        <article class="report-metric">
            <div class="stat-label">Baki</div>
            <strong>RM {{ number_format((float) $balance, 2) }}</strong>
            <span>Pendapatan tolak perbelanjaan</span>
        </article>
        <article class="report-metric">
            <div class="stat-label">Transaksi</div>
            <strong>{{ $transactionCount }}</strong>
            <span>Dalam tempoh dipilih</span>
        </article>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <article class="card report-panel finance-graph-card h-100">
                <div class="card-body">
                    <div class="report-panel-header">
                        <div>
                            <p class="stat-label mb-1">Trend Kewangan</p>
                            <h2 class="h5 mb-0">Pendapatan vs Perbelanjaan</h2>
                        </div>
                    </div>

                    <div class="finance-line-chart">
                        <svg viewBox="0 0 620 240" role="img" aria-label="Graf kewangan">
                            <line x1="36" y1="204" x2="584" y2="204" class="chart-axis" />
                            <line x1="36" y1="148" x2="584" y2="148" class="chart-grid" />
                            <line x1="36" y1="92" x2="584" y2="92" class="chart-grid" />
                            <line x1="36" y1="36" x2="584" y2="36" class="chart-grid" />
                            <polyline points="{{ $incomePoints }}" class="chart-line chart-line-income" />
                            <polyline points="{{ $expensePoints }}" class="chart-line chart-line-expense" />
                            @foreach($chartItems->values() as $item)
                                @php
                                    $x = 36 + (($loop->index / $pointCount) * 548);
                                    $incomeY = 204 - (($item['income'] / $highestChartValue) * 168);
                                    $expenseY = 204 - (($item['expenses'] / $highestChartValue) * 168);
                                @endphp
                                <circle
                                    cx="{{ $x }}"
                                    cy="{{ $incomeY }}"
                                    r="5"
                                    class="chart-dot chart-dot-income"
                                    tabindex="0"
                                    data-chart-label="{{ $item['label'] }}"
                                    data-chart-type="Pendapatan"
                                    data-chart-value="RM {{ number_format($item['income'], 2) }}"
                                />
                                <circle
                                    cx="{{ $x }}"
                                    cy="{{ $expenseY }}"
                                    r="5"
                                    class="chart-dot chart-dot-expense"
                                    tabindex="0"
                                    data-chart-label="{{ $item['label'] }}"
                                    data-chart-type="Perbelanjaan"
                                    data-chart-value="RM {{ number_format($item['expenses'], 2) }}"
                                />
                            @endforeach
                        </svg>
                        <div class="finance-chart-tooltip" role="status" aria-live="polite"></div>
                        <div class="finance-chart-labels">
                            @foreach($chartItems as $item)
                                <span>{{ $item['label'] }}</span>
                            @endforeach
                        </div>
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
                    <h2 class="h5 mb-3">Kategori Utama</h2>

                    <div class="mb-4">
                        <p class="stat-label mb-2">Pendapatan</p>
                        @forelse($incomeCategories as $category)
                            <div class="report-breakdown-item">
                                <div class="d-flex justify-content-between gap-3">
                                    <strong>{{ $category['category'] }}</strong>
                                    <span>RM {{ number_format($category['total'], 2) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">Tiada pendapatan.</p>
                        @endforelse
                    </div>

                    <div>
                        <p class="stat-label mb-2">Perbelanjaan</p>
                        @forelse($expenseCategories as $category)
                            <div class="report-breakdown-item">
                                <div class="d-flex justify-content-between gap-3">
                                    <strong>{{ $category['category'] }}</strong>
                                    <span>RM {{ number_format($category['total'], 2) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">Tiada perbelanjaan.</p>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>
    </div>

    <article class="card report-panel">
        <div class="card-body">
            <div class="report-panel-header">
                <h2 class="h5 mb-0">Senarai Transaksi</h2>
                <span class="stat-meta">{{ $transactionCount }} rekod</span>
            </div>
            <div class="table-responsive">
                <table class="table mobile-records mb-0">
                    <thead>
                        <tr>
                            <th>Tarikh</th>
                            <th>Jenis</th>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td data-label="Tarikh">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td data-label="Jenis"><span class="badge bg-secondary">{{ $transaction->type }}</span></td>
                                <td data-label="Kategori">{{ $transaction->category }}</td>
                                <td data-label="Keterangan">{{ $transaction->description }}</td>
                                <td data-label="Jumlah">RM {{ number_format((float) $transaction->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Tiada transaksi untuk tempoh ini.</td>
                            </tr>
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
    document.querySelectorAll('.finance-line-chart').forEach((chart) => {
        const tooltip = chart.querySelector('.finance-chart-tooltip');
        const dots = chart.querySelectorAll('.chart-dot[data-chart-label]');

        const showTooltip = (dot) => {
            const dotBox = dot.getBoundingClientRect();
            const chartBox = chart.getBoundingClientRect();
            const left = dotBox.left - chartBox.left;
            const top = dotBox.top - chartBox.top;

            tooltip.innerHTML = `
                <strong>${dot.dataset.chartLabel}</strong>
                <span>${dot.dataset.chartType}: ${dot.dataset.chartValue}</span>
            `;
            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${top}px`;
            tooltip.classList.toggle('is-below', top < 72);
            tooltip.classList.toggle('is-right', left < 100);
            tooltip.classList.toggle('is-left', left > chartBox.width - 100);
            tooltip.classList.add('is-visible');
        };

        dots.forEach((dot) => {
            dot.addEventListener('mouseenter', () => showTooltip(dot));
            dot.addEventListener('focus', () => showTooltip(dot));
            dot.addEventListener('mouseleave', () => tooltip.classList.remove('is-visible'));
            dot.addEventListener('blur', () => tooltip.classList.remove('is-visible'));
        });
    });
</script>
@endpush
