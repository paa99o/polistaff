@extends('layouts.app', ['title' => 'Kewangan'])

@section('content')
@php
    $typeLabels = ['income' => 'Pendapatan', 'expense' => 'Perbelanjaan'];
    $statusLabels = ['active' => 'Aktif', 'reversed' => 'Dibatalkan'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <p class="dashboard-kicker mb-1">Lejar kewangan</p>
        <h1 class="h3 mb-0">Transaksi Kewangan</h1>
        <p class="text-muted mb-0">Rekod pendapatan, perbelanjaan dan resit rasmi kelab.</p>
    </div>
    @can('manage-finances')
        <a class="btn btn-danger" href="{{ route('transactions.create') }}">
            <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Transaksi Baru
        </a>
    @endcan
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label" for="type">Jenis</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Semua jenis</option>
                    @foreach($typeLabels as $type => $label)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach($statusLabels as $status => $label)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="category">Kategori</label>
                <input class="form-control" id="category" name="category" value="{{ request('category') }}" placeholder="Yuran">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="from">Dari</label>
                <input class="form-control" id="from" type="date" name="from" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="to">Hingga</label>
                <input class="form-control" id="to" type="date" name="to" value="{{ request('to') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-danger w-100">Tapis</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mobile-records align-middle mb-0">
            <thead>
                <tr>
                    <th>Resit</th>
                    <th>Jenis</th>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th>Tarikh</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td data-label="Resit"><strong>{{ $transaction->receipt_number }}</strong></td>
                        <td data-label="Jenis">{{ $typeLabels[$transaction->type] ?? $transaction->type }}</td>
                        <td data-label="Keterangan">
                            {{ $transaction->description }}
                            <div class="small text-muted">{{ $transaction->category }}</div>
                        </td>
                        <td data-label="Jumlah">RM {{ number_format((float) $transaction->amount, 2) }}</td>
                        <td data-label="Tarikh">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                        <td data-label="Status"><span class="badge bg-secondary">{{ $statusLabels[$transaction->status] ?? $transaction->status }}</span></td>
                        <td data-label="Tindakan"><a class="btn btn-sm btn-outline-danger" href="{{ route('transactions.show', $transaction) }}">Lihat Resit</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="dashboard-empty-state my-3">
                                <span class="stat-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
                                <p class="text-muted mb-0">Tiada transaksi untuk tapisan ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
