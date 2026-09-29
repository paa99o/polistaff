@extends('layouts.app', ['title' => 'Maklumat Bayaran PoliMart'])

@section('content')
<div class="polimart-page">
    <section class="polimart-form-shell">
        <a class="btn btn-light polimart-back-button mb-4" href="{{ route('polimart.index') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke PoliMart
        </a>

        <div class="polimart-sell-panel polimart-sell-panel-wide">
            <h1>Maklumat Bayaran Saya</h1>
            <p>Maklumat ini dipaparkan kepada pembeli selepas mereka memilih kaedah bayaran untuk pesanan anda.</p>
            @if($paymentProfile?->qr_code_path)
                <div class="mb-3">
                    <p class="form-label">QR semasa</p>
                    <img src="{{ asset('storage/'.$paymentProfile->qr_code_path) }}" alt="QR bayaran penjual" style="max-width: 240px; max-height: 240px">
                </div>
            @endif
            <form method="post" action="{{ route('polimart.payment-settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('put')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="qr_code">QR bayaran</label>
                        <input class="form-control" id="qr_code" type="file" name="qr_code" accept="image/*">
                        <div class="form-text">Muat naik imej QR. Maksimum 4 MB.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bank_name">Nama bank</label>
                        <input class="form-control" id="bank_name" name="bank_name" value="{{ old('bank_name', $paymentProfile?->bank_name) }}" maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="account_name">Nama pemegang akaun</label>
                        <input class="form-control" id="account_name" name="account_name" value="{{ old('account_name', $paymentProfile?->account_name) }}" maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="account_number">Nombor akaun</label>
                        <input class="form-control" id="account_number" name="account_number" value="{{ old('account_number', $paymentProfile?->account_number) }}" maxlength="80">
                    </div>
                </div>
                <button class="btn btn-danger mt-3" type="submit">Simpan Maklumat Bayaran</button>
            </form>
        </div>
    </section>
</div>
@endsection
