@extends('layouts.app', ['title' => 'Tuntutan Baru'])

@section('content')
<div class="card">
    <div class="card-body">
        <h1 class="h4 soft-panel-title">Tuntutan Perbelanjaan Baru</h1>
        <form method="post" action="{{ route('claims.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="title">Tajuk</label>
                    <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                    @include('partials.errors', ['name' => 'title'])
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="amount">Jumlah</label>
                    <input class="form-control @error('amount') is-invalid @enderror" id="amount" type="number" step="0.01" name="amount" value="{{ old('amount') }}" required>
                    @include('partials.errors', ['name' => 'amount'])
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="claim_date">Tarikh</label>
                    <input class="form-control @error('claim_date') is-invalid @enderror" id="claim_date" type="date" name="claim_date" value="{{ old('claim_date', now()->toDateString()) }}" required>
                    @include('partials.errors', ['name' => 'claim_date'])
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="category">Kategori</label>
                    <input class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category') }}" required>
                    @include('partials.errors', ['name' => 'category'])
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="receipt">Resit</label>
                    <input class="form-control @error('receipt') is-invalid @enderror" id="receipt" type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required>
                    @include('partials.errors', ['name' => 'receipt'])
                    <div class="form-text">Format: JPG, PNG atau PDF. Maksimum 4MB.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Keterangan</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @include('partials.errors', ['name' => 'description'])
                </div>
            </div>
            <button class="btn btn-danger mt-3">Hantar Tuntutan</button>
        </form>
    </div>
</div>
@endsection
