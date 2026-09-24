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
                    <div class="mb-3">
                        <label class="form-label" for="bill-selection">Bulan yang hendak dibayar</label>
                        <select class="form-select @error('bill_ids') is-invalid @enderror" id="bill-selection" required>
                            <option value="">-- Pilih bilangan bulan --</option>
                            @foreach($bills as $bill)
                                <option value="count:{{ $loop->iteration }}">Bayar {{ $loop->iteration }} bulan</option>
                            @endforeach
                            @if($overdueBills->isNotEmpty())
                                <option value="overdue">Tunggakan ({{ $overdueBills->count() }} bulan)</option>
                            @endif
                        </select>
                        <div class="form-text">Bulan paling lama akan dipilih dahulu. Pilihan tunggakan hanya memaparkan bil yang telah melepasi tarikh akhir.</div>
                        @include('partials.errors', ['name' => 'bill_ids'])
                    </div>
                    <div id="selected-bills-summary" class="alert alert-info d-none mb-3" aria-live="polite"></div>
                    <div id="selected-bills-inputs"></div>
                    <div class="small text-muted mb-3" id="bill-selection-empty">Sila pilih bilangan bulan atau tunggakan untuk melihat pecahan bayaran.</div>
                    <div class="table-responsive d-none mb-3" id="selected-bills-table-wrapper">
                        <table class="table table-sm align-middle mb-0 fee-selection-table">
                            <caption class="visually-hidden">Bulan yang dipilih untuk bayaran</caption>
                            <thead><tr><th>Bulan</th><th>Baki</th><th>Status</th></tr></thead>
                            <tbody id="selected-bills-table"></tbody>
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
                <form id="payment-submission-form" method="post" action="{{ route('payments.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="amount">Jumlah Bayaran</label>
                            <input class="form-control @error('amount') is-invalid @enderror" id="amount" type="number" step="0.01" min="0.01" max="{{ number_format($outstanding, 2, '.', '') }}" name="amount" value="{{ old('amount', '') }}" readonly required>
                            @include('partials.errors', ['name' => 'amount'])
                            <div class="form-text">Jumlah dikira secara automatik berdasarkan pilihan bulan.</div>
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
@php($billPayload = $bills->values()->map(fn ($bill) => [
    'id' => $bill->id,
    'month' => $bill->billing_month->translatedFormat('F Y'),
    'amount' => $bill->remainingAmount(),
    'status' => $bill->status === 'partial' ? 'Sebahagian' : 'Belum dibayar',
    'overdue' => $overdueBills->contains('id', $bill->id),
])->values())
<script>
const amountField = document.getElementById('amount');
const billSelection = document.getElementById('bill-selection');
const selectedBillsSummary = document.getElementById('selected-bills-summary');
const selectedBillsTableWrapper = document.getElementById('selected-bills-table-wrapper');
const selectedBillsTable = document.getElementById('selected-bills-table');
const selectedBillsInputs = document.getElementById('selected-bills-inputs');
const billSelectionEmpty = document.getElementById('bill-selection-empty');
const bills = @json($billPayload);
const oldBillIds = @json(array_map('strval', (array) old('bill_ids', request('bill_id') ? [request('bill_id')] : [])));
function selectedBillsFor(value) {
    if (value === 'overdue') return bills.filter(bill => bill.overdue);
    const count = Number(value.replace('count:', ''));
    return Number.isInteger(count) ? bills.slice(0, count) : [];
}
function updateBillSelection() {
    const selectedBills = selectedBillsFor(billSelection.value);
    selectedBillsInputs.innerHTML = selectedBills.map(bill => `<input type="hidden" name="bill_ids[]" value="${bill.id}" form="payment-submission-form">`).join('');
    selectedBillsTable.innerHTML = selectedBills.map(bill => `<tr><td>${bill.month}</td><td>RM ${Number(bill.amount).toFixed(2)}</td><td>${bill.status}</td></tr>`).join('');
    const total = selectedBills.reduce((sum, bill) => sum + Number(bill.amount), 0);
    amountField.value = total ? total.toFixed(2) : '';
    selectedBillsSummary.textContent = selectedBills.length ? `${selectedBills.length} bulan dipilih · Jumlah: RM ${total.toFixed(2)}` : '';
    selectedBillsSummary.classList.toggle('d-none', selectedBills.length === 0);
    selectedBillsTableWrapper.classList.toggle('d-none', selectedBills.length === 0);
    billSelectionEmpty.classList.toggle('d-none', selectedBills.length > 0);
}
billSelection?.addEventListener('change', updateBillSelection);
const methodSelect = document.getElementById('payment_method');
function updateMethodDetails() {
    document.querySelectorAll('.js-method-detail').forEach(detail => detail.classList.toggle('d-none', detail.dataset.method !== methodSelect.value));
}
methodSelect.addEventListener('change', updateMethodDetails);
updateMethodDetails();
if (billSelection && oldBillIds.length) {
    const oldCount = oldBillIds.length === 1 && bills.some(bill => String(bill.id) === oldBillIds[0]) ? `count:1` : `count:${oldBillIds.length}`;
    billSelection.value = oldCount;
}
if (billSelection) updateBillSelection();
document.querySelector('form').addEventListener('submit', function (event) {
    if (billSelection && !selectedBillsInputs.querySelector('input')) { event.preventDefault(); alert('Sila pilih bulan yang hendak dibayar.'); }
});
</script>
@endsection
