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
                        <table class="table table-sm align-middle mb-0 fee-selection-table">
                            <caption class="visually-hidden">Bil tertunggak dan belum dibayar yang boleh dipilih untuk bayaran</caption>
                            <thead><tr><th><input class="form-check-input" type="checkbox" id="select-all-fees" aria-label="Pilih semua bulan"></th><th>Bulan</th><th>Baki</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($bills as $bill)
                                <tr>
                                    <td><input class="form-check-input js-bill-checkbox" type="checkbox" name="bill_ids[]" value="{{ $bill->id }}" data-amount="{{ number_format($bill->remainingAmount(), 2, '.', '') }}" @checked((string) request('bill_id') === (string) $bill->id || in_array($bill->id, (array) old('bill_ids', [])))></td>
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

                <section class="mb-4" aria-labelledby="paid-current-year-fees-title">
                    <h2 class="h6 mb-2" id="paid-current-year-fees-title">Bayaran Tahun Semasa ({{ now()->year }})</h2>
                    <p class="small text-muted mb-2">Bulan yang telah selesai dibayar turut dipaparkan sebagai rekod dan tidak boleh dipilih semula.</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 fee-selection-table">
                            <caption class="visually-hidden">Bil yuran tahun semasa yang telah selesai dibayar</caption>
                            <thead><tr><th>Bulan</th><th>Jumlah Dibayar</th><th>Status</th></tr></thead>
                            <tbody>
                            @forelse($paidCurrentYearBills as $bill)
                                <tr>
                                    <td>{{ $bill->billing_month->format('F Y') }}</td>
                                    <td>RM {{ number_format((float) $bill->paid_amount, 2) }}</td>
                                    <td><span class="badge bg-success">Selesai</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">Belum ada bayaran yuran yang selesai bagi tahun {{ now()->year }}.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                <form method="post" action="{{ route('payments.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="amount">Jumlah Bayaran</label>
                            <input class="form-control @error('amount') is-invalid @enderror" id="amount" type="number" step="0.01" min="0.01" max="{{ number_format($outstanding, 2, '.', '') }}" name="amount" value="{{ old('amount', '') }}" readonly required>
                            @include('partials.errors', ['name' => 'amount'])
                            <div class="form-text">Tandakan bulan yang ingin dibayar. Jumlah dikira secara automatik.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="payment_method">Kaedah Bayaran</label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                                @foreach(['DuitNow QR', 'Transfer', 'Cash'] as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method', 'DuitNow QR') === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                            @include('partials.errors', ['name' => 'payment_method'])
                        </div>
                        <div class="col-12">
                            <div id="payment-method-details" class="payment-method-details" data-qr="{{ 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data='.urlencode('POLIBEST Kelab Staf - DuitNow') }}">
                                <div class="method-detail js-method-detail" data-method="DuitNow QR"><img class="payment-qr" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode('POLIBEST Kelab Staf - DuitNow') }}" alt="QR DuitNow Kelab Staf"><span>Imbas QR DuitNow Kelab Staf untuk membuat bayaran.</span></div>
                                <div class="method-detail js-method-detail d-none" data-method="Transfer"><strong>Maklumat akaun Kelab Staf</strong><span>Bank: tetapkan nama bank kelab<br>No. Akaun: tetapkan nombor akaun kelab</span><small>Sila gunakan nama penuh sebagai rujukan transaksi.</small></div>
                                <div class="method-detail js-method-detail d-none" data-method="Cash"><strong>Bayaran tunai</strong><span>Sila serahkan bayaran tunai kepada bendahari Kelab Staf.</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="payment_date">Tarikh Bayaran</label>
                            <input class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                            @include('partials.errors', ['name' => 'payment_date'])
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="proof">Fail Bukti Bayaran <span class="text-muted">(pilihan)</span></label>
                            <input class="form-control @error('proof') is-invalid @enderror" id="proof" type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf">
                            @include('partials.errors', ['name' => 'proof'])
                            <div class="form-text">Boleh dimuat naik sekarang atau dihantar kemudian. Format: JPG, PNG atau PDF. Maksimum 4MB.</div>
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
const feeCheckboxes = [...document.querySelectorAll('.js-bill-checkbox')];
const amountField = document.getElementById('amount');
const selectAll = document.getElementById('select-all-fees');
function updateFeeTotal() {
    const total = feeCheckboxes.filter(input => input.checked).reduce((sum, input) => sum + Number(input.dataset.amount), 0);
    amountField.value = total ? total.toFixed(2) : '';
    if (selectAll) selectAll.checked = feeCheckboxes.length > 0 && feeCheckboxes.every(input => input.checked);
}
feeCheckboxes.forEach(input => input.addEventListener('change', updateFeeTotal));
if (selectAll) selectAll.addEventListener('change', () => { feeCheckboxes.forEach(input => input.checked = selectAll.checked); updateFeeTotal(); });
const methodSelect = document.getElementById('payment_method');
function updateMethodDetails() {
    document.querySelectorAll('.js-method-detail').forEach(detail => detail.classList.toggle('d-none', detail.dataset.method !== methodSelect.value));
}
methodSelect.addEventListener('change', updateMethodDetails);
updateMethodDetails();
updateFeeTotal();
document.querySelector('form').addEventListener('submit', function (event) {
    if (!feeCheckboxes.some(input => input.checked)) { event.preventDefault(); alert('Sila pilih sekurang-kurangnya satu bulan untuk dibayar.'); }
});
</script>
@endsection
