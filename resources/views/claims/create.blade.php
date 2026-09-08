@extends('layouts.app', ['title' => 'Tuntutan Perbelanjaan'])

@section('content')
@php
    $isEditing = isset($claim) && $claim;
    $isResubmitting = $resubmission ?? false;
    $formAction = $isResubmitting ? route('claims.resubmit', $claim) : ($isEditing ? route('claims.update', $claim) : route('claims.store'));
    $pageTitle = $isResubmitting ? 'Hantar Semula Tuntutan' : ($isEditing ? 'Sunting Tuntutan' : 'Tuntutan Baru');
@endphp
<div class="card">
    <div class="card-body">
        <h1 class="h4 soft-panel-title">{{ $pageTitle }}</h1>
        @if($isResubmitting && $claim->review_notes)
            <div class="alert alert-danger"><strong>Sebab ditolak:</strong> {{ $claim->review_notes }}</div>
        @endif
        <form method="post" action="{{ $formAction }}" enctype="multipart/form-data">
            @csrf
            @if($isEditing)<input type="hidden" name="_method" value="PUT">@endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="title">Tajuk</label>
                    <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $claim->title ?? '') }}" required>
                    @include('partials.errors', ['name' => 'title'])
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="amount">Jumlah</label>
                    <input class="form-control @error('amount') is-invalid @enderror" id="amount" type="number" step="0.01" name="amount" value="{{ old('amount', $claim->amount ?? '') }}" required>
                    @include('partials.errors', ['name' => 'amount'])
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="claim_date">Tarikh</label>
                    <input class="form-control @error('claim_date') is-invalid @enderror" id="claim_date" type="date" name="claim_date" value="{{ old('claim_date', $claim?->claim_date?->format('Y-m-d') ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    @include('partials.errors', ['name' => 'claim_date'])
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="category">Kategori</label>
                    <input class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $claim->category ?? '') }}" required>
                    @include('partials.errors', ['name' => 'category'])
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="receipt">Resit</label>
                    <input class="form-control @error('receipt') is-invalid @enderror" id="receipt" type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" @required(! $isEditing || $isResubmitting)>
                    @include('partials.errors', ['name' => 'receipt'])
                    <div class="form-text">Format: JPG, PNG atau PDF. Maksimum 4MB.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Keterangan</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $claim->description ?? '') }}</textarea>
                    @include('partials.errors', ['name' => 'description'])
                </div>
            </div>
            <button class="btn btn-danger mt-3">{{ $isResubmitting ? 'Hantar Semula' : ($isEditing ? 'Simpan Perubahan' : 'Hantar Tuntutan') }}</button>
        </form>
    </div>
</div>
@endsection
