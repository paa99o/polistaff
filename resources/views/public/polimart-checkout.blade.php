@extends('layouts.public', ['title' => 'Checkout PoliMart'])

@section('content')
<section class="public-container public-listing-hero"><p class="public-eyebrow">Langkah terakhir</p><h1>Teruskan Pembelian</h1><p>Isi maklumat penghantaran. Anda tidak perlu mempunyai akaun POLIBEST.</p>@auth<p>Pesanan ini akan dipautkan ke akaun anda dan boleh dilihat semula di menu Belian Saya.</p>@else<p>Pautan semakan pesanan akan dipaparkan selepas checkout dan dihantar ke e-mel anda.</p>@endauth</section>
<section class="public-container public-checkout-section"><div class="public-checkout-layout"><div><h2>Ringkasan Pesanan</h2>@foreach($items as $line)<div class="public-checkout-item"><div><strong>{{ $line['item']->name }}</strong><span>{{ $line['quantity'] }} × RM {{ number_format((float) $line['item']->price, 2) }}</span></div><strong>RM {{ number_format($line['lineTotal'], 2) }}</strong></div>@endforeach<div class="public-summary-lines"><div><span>Subtotal</span><strong>RM {{ number_format($subtotal, 2) }}</strong></div><div><span>Penghantaran</span><strong>RM {{ number_format($shippingFee, 2) }}</strong></div><div class="public-summary-total"><span>Jumlah</span><strong>RM {{ number_format($total, 2) }}</strong></div></div></div><div><h2>Maklumat Anda</h2><form id="polimart-checkout-form" method="post" action="{{ route('polimart.checkout.store') }}">@csrf<div class="mb-3"><label class="form-label" for="customer_name">Nama penuh <span class="text-danger">*</span></label><input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required></div><div class="mb-3"><label class="form-label" for="customer_email">E-mel <span class="text-danger">*</span></label><input class="form-control" id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}" required></div><div class="mb-3"><label class="form-label" for="customer_phone">Nombor telefon <span class="text-danger">*</span></label><input class="form-control" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="01X-XXXXXXX" required></div><div class="mb-3"><label class="form-label" for="address_line_1">Alamat penghantaran <span class="text-danger">*</span></label><input class="form-control mb-2" id="address_line_1" name="address_line_1" value="{{ old('address_line_1') }}" placeholder="Alamat Baris 1" required><input class="form-control" name="address_line_2" value="{{ old('address_line_2') }}" placeholder="Alamat Baris 2 (optional)"></div><div class="row g-3 mb-3"><div class="col-md-5"><label class="form-label" for="postcode">Poskod <span class="text-danger">*</span></label><input class="form-control" id="postcode" name="postcode" value="{{ old('postcode') }}" required></div><div class="col-md-7"><label class="form-label" for="city">Bandar <span class="text-danger">*</span></label><input class="form-control" id="city" name="city" value="{{ old('city') }}" required></div></div><div class="mb-3"><label class="form-label" for="state">Negeri <span class="text-danger">*</span></label><select class="form-select" id="state" name="state" required><option value="">Pilih negeri</option>@foreach(['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang','Perak','Perlis','Pulau Pinang','Sabah','Sarawak','Selangor','Terengganu','W.P. Kuala Lumpur','W.P. Labuan','W.P. Putrajaya'] as $state)<option value="{{ $state }}" @selected(old('state') === $state)>{{ $state }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label" for="note">Nota</label><textarea class="form-control" id="note" name="note" rows="3" placeholder="Nota untuk pesanan (optional)">{{ old('note') }}</textarea></div>
@php
    $hasQr = filled($paymentProfile?->qr_code_path);
    $hasBank = filled($paymentProfile?->bank_name) && filled($paymentProfile?->account_name) && filled($paymentProfile?->account_number);
    $hasFpx = $hasBank;
    $selectedPayment = old('payment_method', $hasQr ? 'qr' : 'fpx');
@endphp
<fieldset class="mb-4">
    <legend class="form-label">Pilih kaedah bayaran</legend>
    @if($hasQr)
        <label class="form-check border rounded p-3 mb-2"><input class="form-check-input" type="radio" name="payment_method" value="qr" @checked($selectedPayment === 'qr')><span class="form-check-label">Bayar dengan QR</span><img class="d-block mt-2" src="{{ asset('storage/'.$paymentProfile->qr_code_path) }}" alt="QR bayaran penjual" style="max-width: 180px; max-height: 180px"></label>
    @endif
    @if($hasFpx)
        <label class="form-check border rounded p-3 mb-2"><input class="form-check-input" type="radio" name="payment_method" value="fpx" @checked($selectedPayment === 'fpx')><span class="form-check-label">FPX · Perbankan dalam talian</span><span class="d-block mt-2">Pilih bank anda untuk meneruskan proses pembayaran. Bayaran disahkan selepas bukti transaksi disemak.</span></label>
        <div class="ms-4 mb-3" id="fpx-bank-field">
            <label class="form-label" for="fpx_bank">Bank anda</label>
            <select class="form-select @error('fpx_bank') is-invalid @enderror" id="fpx_bank" name="fpx_bank">
                <option value="">Pilih bank</option>
                @foreach($fpxBanks as $bank)
                    <option value="{{ $bank }}" @selected(old('fpx_bank') === $bank)>{{ $bank }}</option>
                @endforeach
            </select>
            @include('partials.errors', ['name' => 'fpx_bank'])
        </div>
    @endif
    @unless($hasQr || $hasFpx)
        <div class="alert alert-warning mb-0">Penjual belum menyediakan QR atau butiran bank. Checkout belum boleh diteruskan.</div>
    @endunless
</fieldset>
<label class="form-check mb-4"><input class="form-check-input" type="checkbox" name="terms" value="1" required><span class="form-check-label">Saya bersetuju dengan terma pembelian dan dasar privasi.</span></label><button class="btn btn-primary btn-lg w-100" type="submit" @disabled(! $hasQr && ! $hasFpx)>Hantar Pesanan · RM {{ number_format($total, 2) }}</button></form></div></div></section>
<script>
(() => {
    const bankField = document.getElementById('fpx-bank-field');
    if (!bankField) return;
    const bankSelect = document.getElementById('fpx_bank');
    const checkoutForm = document.getElementById('polimart-checkout-form');
    const updateFpxBank = () => {
        const isFpx = document.querySelector('input[name="payment_method"]:checked')?.value === 'fpx';
        bankField.hidden = !isFpx;
        bankSelect.required = isFpx;
        if (isFpx) {
            checkoutForm.setAttribute('target', '_blank');
            checkoutForm.setAttribute('rel', 'noopener');
        } else {
            checkoutForm.removeAttribute('target');
            checkoutForm.removeAttribute('rel');
        }
    };
    document.querySelectorAll('input[name="payment_method"]').forEach((input) => input.addEventListener('change', updateFpxBank));
    updateFpxBank();
})();
</script>
@endsection
