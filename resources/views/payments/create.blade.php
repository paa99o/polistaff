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
                        <label class="form-label" for="months-selection">Tempoh bayaran yuran</label>
                        <select class="form-select @error('months') is-invalid @enderror" id="months-selection" name="months" required>
                            <option value="">-- Pilih tempoh bayaran --</option>
                            @foreach($paymentMonthOptions as $months => $amount)
                                <option value="{{ $months }}" @selected((string) old('months') === (string) $months)>Bayar {{ $months }} bulan (RM {{ number_format($amount, 2) }})</option>
                            @endforeach
                        </select>
                        <div class="form-text">Bayaran dikira RM10 bagi setiap bulan. Sistem akan tambah bil bulan akan datang apabila perlu, kemudian mengagihkan bayaran bermula daripada tunggakan paling lama.</div>
                        @include('partials.errors', ['name' => 'months'])
                    </div>
                    <div id="selected-bills-summary" class="alert alert-info d-none mb-3" aria-live="polite"></div>
                    <div class="small text-muted mb-3" id="bill-selection-empty">Pilih tempoh untuk melihat agihan bayaran bermula daripada bulan paling lama.</div>
                    <div class="table-responsive d-none mb-3" id="selected-bills-table-wrapper">
                        <table class="table table-sm align-middle mb-0 fee-selection-table">
                            <caption class="visually-hidden">Anggaran agihan bayaran mengikut bil bulan paling lama dahulu</caption>
                            <thead><tr><th>Bulan</th><th>Bayaran</th><th>Baki sebelum</th><th>Baki selepas</th><th>Status</th></tr></thead>
                            <tbody id="selected-bills-table"></tbody>
                        </table>
                    </div>
                @else
                    @if($pendingAmount > 0)
                        <div class="alert alert-info">Semua baki yuran yang dipilih kini diliputi bayaran menunggu semakan. Anda boleh menghantar bayaran baharu selepas semakan selesai.</div>
                    @else
                        <div class="alert alert-success">Tiada tunggakan sedia ada. Anda masih boleh memilih pakej untuk bulan semasa dan bulan akan datang.</div>
                    @endif
                @endif

                @if($bills->isEmpty())
                    <div class="mb-3">
                        <label class="form-label" for="months-selection">Tempoh bayaran yuran</label>
                        <select class="form-select @error('months') is-invalid @enderror" id="months-selection" name="months" required>
                            <option value="">-- Pilih tempoh bayaran --</option>
                            @foreach($paymentMonthOptions as $months => $amount)
                                <option value="{{ $months }}" @selected((string) old('months') === (string) $months)>Bayar {{ $months }} bulan (RM {{ number_format($amount, 2) }})</option>
                            @endforeach
                        </select>
                        <div class="form-text">Bayaran dikira RM10 bagi setiap bulan. Bil bulan akan datang disediakan apabila pakej dihantar.</div>
                        @include('partials.errors', ['name' => 'months'])
                    </div>
                    <div id="selected-bills-summary" class="alert alert-info d-none mb-3" aria-live="polite"></div>
                    <div class="small text-muted mb-3" id="bill-selection-empty">Pilih tempoh untuk melihat anggaran bulan yang akan dibayar.</div>
                    <div class="table-responsive d-none mb-3" id="selected-bills-table-wrapper">
                        <table class="table table-sm align-middle mb-0 fee-selection-table">
                            <caption class="visually-hidden">Anggaran agihan bayaran mengikut bulan</caption>
                            <thead><tr><th>Bulan</th><th>Bayaran</th><th>Baki sebelum</th><th>Baki selepas</th><th>Status</th></tr></thead>
                            <tbody id="selected-bills-table"></tbody>
                        </table>
                    </div>
                @endif

                <form id="payment-submission-form" method="post" action="{{ route('payments.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="amount">Jumlah Bayaran</label>
                            <input class="form-control @error('amount') is-invalid @enderror" id="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', '') }}" readonly required>
                            @include('partials.errors', ['name' => 'amount'])
                            <div class="form-text">Jumlah dikira automatik: RM10 × bilangan bulan.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="payment_method">Kaedah Bayaran</label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                                @foreach($paymentOptions as $method => $label)
                                    <option value="{{ $method }}" @selected(old('payment_method', array_key_first($paymentOptions)) === $method)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @include('partials.errors', ['name' => 'payment_method'])
                        </div>
                        <div class="col-12">
                            <div id="payment-method-details" class="payment-method-details">
                                @if($financePaymentQrPath)<div class="method-detail js-method-detail" data-method="DuitNow QR"><img class="payment-qr" src="{{ asset('storage/'.$financePaymentQrPath) }}" alt="QR bayaran yuran POLIBEST"><span>Selepas membayar, muat naik bukti transaksi untuk semakan bendahari.</span></div>@endif
                                @if($financeBankDetails['bank_name'] && $financeBankDetails['account_name'] && $financeBankDetails['account_number'])<div class="method-detail js-method-detail d-none" data-method="Transfer"><strong>Maklumat akaun Kelab Staf</strong><span>{{ $financeBankDetails['bank_name'] }}<br>{{ $financeBankDetails['account_name'] }}<br>No. Akaun: {{ $financeBankDetails['account_number'] }}</span><small>Sila gunakan nama penuh sebagai rujukan transaksi dan lampirkan bukti.</small></div>@endif
                                <div class="method-detail js-method-detail d-none" data-method="Cash"><strong>Bayaran tunai</strong><span>Sila serahkan bayaran kepada bendahari dan minta resit. Bukti bayaran akan disemak sebelum rekod yuran dikemas kini.</span></div>
                            </div>
                        </div>
                        <div class="col-12"><div class="form-text">Tarikh bayaran akan direkod secara automatik apabila borang dihantar.</div></div>
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
                    <button class="btn btn-danger mt-3">Hantar Untuk Semakan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@php($billPayload = $bills->values()->map(fn ($bill) => [
    'id' => $bill->id,
    'month' => $bill->billing_month->translatedFormat('F Y'),
    'amount' => $bill->payment_available_amount,
    'status' => $bill->status === 'partial' ? 'Sebahagian' : 'Belum dibayar',
    'overdue' => $overdueBills->contains('id', $bill->id),
])->values())
<script>
const amountField = document.getElementById('amount');
const monthsSelection = document.getElementById('months-selection');
const selectedBillsSummary = document.getElementById('selected-bills-summary');
const selectedBillsTableWrapper = document.getElementById('selected-bills-table-wrapper');
const selectedBillsTable = document.getElementById('selected-bills-table');
const billSelectionEmpty = document.getElementById('bill-selection-empty');
const bills = @json($billPayload);
const paymentMonthOptions = @json($paymentMonthOptions);
let nextBillingMonth = @json($nextBillingMonth);
const monthlyBillAmount = Number(@json($monthlyBillAmount));
function previewAllocation(total) {
    let remaining = total;
    const allocations = [];
    for (const bill of bills) {
        if (remaining <= 0.005) break;
        const available = Number(bill.amount);
        const payment = Math.min(remaining, available);
        if (payment > 0) allocations.push({ bill, payment });
        remaining -= payment;
    }
    let futureMonth = new Date();
    const latest = bills.length ? bills[bills.length - 1].month : null;
    if (latest) {
        // Month labels are localized, so use a server-provided ISO anchor instead.
        futureMonth = new Date(@json(\Illuminate\Support\Carbon::parse($nextBillingMonth)->format('Y-m-d')) + 'T00:00:00');
    } else {
        futureMonth = new Date(@json(now()->startOfMonth()->format('Y-m-d')) + 'T00:00:00');
    }
    while (remaining > 0.005) {
        const payment = Math.min(remaining, monthlyBillAmount);
        const monthLabel = futureMonth.toLocaleDateString('ms-MY', { month: 'long', year: 'numeric' });
        allocations.push({ bill: { month: monthLabel, amount: monthlyBillAmount, status: 'Belum dijana', overdue: false }, payment });
        remaining -= payment;
        futureMonth.setMonth(futureMonth.getMonth() + 1);
    }
    return allocations;
}
function updateBillSelection() {
    const months = Number(monthsSelection.value);
    const total = Number(paymentMonthOptions[months] || 0);
    const allocations = previewAllocation(total);
    selectedBillsTable.innerHTML = allocations.map(({ bill, payment }) => {
        const before = Number(bill.amount);
        const after = Math.max(0, before - payment);
        return `<tr><td>${bill.month}</td><td>RM ${payment.toFixed(2)}</td><td>RM ${before.toFixed(2)}</td><td>RM ${after.toFixed(2)}</td><td>${after > 0.005 ? 'Sebahagian' : 'Selesai selepas bayaran'}</td></tr>`;
    }).join('');
    amountField.value = total ? total.toFixed(2) : '';
    selectedBillsSummary.textContent = months ? `Bayaran ${months} bulan: RM ${total.toFixed(2)} · Agihan bermula dari bil paling lama.` : '';
    selectedBillsSummary.classList.toggle('d-none', !months);
    selectedBillsTableWrapper.classList.toggle('d-none', allocations.length === 0);
    billSelectionEmpty.classList.toggle('d-none', !!months || bills.length === 0);
}
monthsSelection?.addEventListener('change', updateBillSelection);
const methodSelect = document.getElementById('payment_method');
function updateMethodDetails() {
    document.querySelectorAll('.js-method-detail').forEach(detail => detail.classList.toggle('d-none', detail.dataset.method !== methodSelect.value));
}
methodSelect.addEventListener('change', updateMethodDetails);
updateMethodDetails();
if (monthsSelection) updateBillSelection();
document.getElementById('payment-submission-form').addEventListener('submit', function (event) {
    if (monthsSelection && !monthsSelection.value) { event.preventDefault(); alert('Sila pilih tempoh bayaran yuran.'); }
});
</script>
@endsection
