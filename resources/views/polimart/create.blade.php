@extends('layouts.app', ['title' => 'Jual Produk PoliMart'])

@section('content')
<div class="polimart-page">
    <section class="polimart-form-shell">
        <a class="btn btn-light polimart-back-button mb-4" href="{{ route('polimart.index') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke PoliMart
        </a>

        <div class="polimart-sell-panel polimart-sell-panel-wide">
            <h1>Jual Produk</h1>
            <form method="post" action="{{ route('polimart.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Produk</label>
                        <input class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <input class="form-control" name="category" value="{{ old('category') }}" placeholder="Makanan / Servis / Pre-loved" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Seller</label>
                        <input class="form-control" name="contact" value="{{ old('contact', auth()->user()->phone) }}" placeholder="No. telefon / WhatsApp" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Gambar Produk</label>
                        <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png">
                        <div class="form-text">Format JPG atau PNG. Maksimum 4MB.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Penerangan</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="Detail produk, pickup point, stok atau nota lain">{{ old('description') }}</textarea>
                    </div>
                </div>
                <button class="btn btn-danger mt-3">Post Produk</button>
            </form>
        </div>
    </section>
</div>
@endsection
