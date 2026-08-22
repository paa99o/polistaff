@extends('layouts.app', ['title' => 'Upload Bukti Bayaran'])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 soft-panel-title">Upload Bukti Bayaran</h1>
                <p class="text-muted">Ahli boleh muat naik bukti bayaran yuran. Bendahari akan semak dan sistem akan jana resit selepas diluluskan.</p>
                <form method="post" action="{{ route('payments.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Jumlah Bayaran</label><input class="form-control" type="number" step="0.01" name="amount" value="{{ old('amount', auth()->user()->fee_balance > 0 ? auth()->user()->fee_balance : '') }}" required></div>
                        <div class="col-md-4"><label class="form-label">Kaedah Bayaran</label><select class="form-select" name="payment_method" required><option value="Online Transfer">Online Transfer</option><option value="Tunai">Tunai</option><option value="DuitNow">DuitNow</option><option value="Bank Islam">Bank Islam</option></select></div>
                        <div class="col-md-4"><label class="form-label">Tarikh Bayaran</label><input class="form-control" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required></div>
                        <div class="col-12"><label class="form-label">Fail Bukti Bayaran</label><input class="form-control" type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required><div class="form-text">Format: JPG, PNG atau PDF. Maksimum 4MB.</div></div>
                        <div class="col-12"><label class="form-label">Catatan</label><textarea class="form-control" name="notes" rows="3" placeholder="Contoh: Bayaran yuran bulan Julai">{{ old('notes') }}</textarea></div>
                    </div>
                    <button class="btn btn-danger mt-3">Hantar Untuk Semakan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
