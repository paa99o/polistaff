@extends('layouts.app', ['title' => 'Muat Naik Bukti Bayaran'])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 soft-panel-title">Muat Naik Bukti Bayaran</h1>
                <p class="text-muted">Bayaran mesti diselesaikan mengikut turutan bulan paling lama dahulu. Bendahari akan semak dan sistem akan jana resit selepas diluluskan.</p>
                @if($bills->isNotEmpty())
                    <div class="alert alert-warning">
                        <strong>Jumlah tunggakan: RM {{ number_format($outstanding, 2) }}</strong>
                        @if($pendingAmount > 0)
                            <div class="small">RM {{ number_format($pendingAmount, 2) }} sedang menunggu semakan dan tidak boleh dihantar semula.</div>
                        @endif
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Bulan</th><th>Baki</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($bills as $bill)
                                <tr>
                                    <td>{{ $bill->billing_month->format('F Y') }} @if($loop->first)<span class="badge bg-danger">Bayar dahulu</span>@endif</td>
                                    <td>RM {{ number_format($bill->remainingAmount(), 2) }}</td>
                                    <td>{{ $bill->status === 'partial' ? 'Sebahagian' : 'Belum dibayar' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-success">Tiada tunggakan yuran untuk dibayar.</div>
                @endif
                <form method="post" action="{{ route('payments.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="amount">Jumlah Bayaran</label>
                            <input class="form-control @error('amount') is-invalid @enderror" id="amount" type="number" step="0.01" min="0.01" max="{{ number_format($outstanding, 2, '.', '') }}" name="amount" value="{{ old('amount', $outstanding > 0 ? number_format($outstanding, 2, '.', '') : '') }}" required>
                            @include('partials.errors', ['name' => 'amount'])
                            @if($bills->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @php($running = 0)
                                    @foreach($bills as $bill)
                                        @php($running += $bill->remainingAmount())
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-fee-amount" data-amount="{{ number_format($running, 2, '.', '') }}">
                                            {{ $loop->first ? 'Bulan paling lama' : 'Hingga '.$bill->billing_month->format('M Y') }}
                                        </button>
                                    @endforeach
                                    <button type="button" class="btn btn-sm btn-outline-danger js-fee-amount" data-amount="{{ number_format($outstanding, 2, '.', '') }}">Semua tunggakan</button>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="payment_method">Kaedah Bayaran</label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                                @foreach(['Online Transfer', 'Tunai', 'DuitNow', 'Bank Islam'] as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method', 'Online Transfer') === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                            @include('partials.errors', ['name' => 'payment_method'])
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="payment_date">Tarikh Bayaran</label>
                            <input class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                            @include('partials.errors', ['name' => 'payment_date'])
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="proof">Fail Bukti Bayaran</label>
                            <input class="form-control @error('proof') is-invalid @enderror" id="proof" type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required>
                            @include('partials.errors', ['name' => 'proof'])
                            <div class="form-text">Format: JPG, PNG atau PDF. Maksimum 4MB.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">Catatan</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" placeholder="Contoh: Bayaran yuran bulan Julai">{{ old('notes') }}</textarea>
                            @include('partials.errors', ['name' => 'notes'])
                        </div>
                    </div>
                    <button class="btn btn-danger mt-3" @disabled($outstanding <= 0)>Hantar Untuk Semakan</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.js-fee-amount').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('amount').value = this.dataset.amount;
    });
});
</script>
@endsection
