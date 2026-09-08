@extends('layouts.app', ['title' => 'Muat Naik Bukti Bayaran'])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 soft-panel-title">Muat Naik Bukti Bayaran</h1>
                <p class="text-muted">Ahli boleh muat naik bukti bayaran yuran. Bendahari akan semak dan sistem akan jana resit selepas diluluskan.</p>
                <form method="post" action="{{ route('payments.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="amount">Jumlah Bayaran</label>
                            <input class="form-control @error('amount') is-invalid @enderror" id="amount" type="number" step="0.01" name="amount" value="{{ old('amount', auth()->user()->fee_balance > 0 ? auth()->user()->fee_balance : '') }}" required>
                            @include('partials.errors', ['name' => 'amount'])
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
                    <button class="btn btn-danger mt-3">Hantar Untuk Semakan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
