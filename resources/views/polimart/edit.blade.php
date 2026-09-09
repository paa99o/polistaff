@extends('layouts.app', ['title' => 'Edit Listing PoliMart'])

@section('content')
<div class="polimart-page">
    <section class="polimart-form-shell">
        <a class="btn btn-light polimart-back-button mb-4" href="{{ route('polimart.show', $item) }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke Listing
        </a>

        <div class="polimart-sell-panel polimart-sell-panel-wide">
            <h1>Edit Listing</h1>
            <div class="polimart-seller-notice" role="note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <p>Pastikan perubahan maklumat masih tepat dan mematuhi peraturan PoliMart.</p>
            </div>
            <form method="post" action="{{ route('polimart.update', $item) }}" enctype="multipart/form-data">
                @csrf
                @method('put')
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama Produk</label><input class="form-control" name="name" value="{{ old('name', $item->name) }}" required></div>
                    <div class="col-md-6"><label class="form-label">Kategori</label><input class="form-control" name="category" value="{{ old('category', $item->category) }}" required></div>
                    <div class="col-md-6"><label class="form-label">Harga</label><input class="form-control" type="number" step="0.01" min="0" name="price" value="{{ old('price', $item->price) }}" required></div>
                    <div class="col-md-6"><label class="form-label">Nombor untuk urusan selepas chat</label><input class="form-control" name="contact" value="{{ old('contact', $item->contact) }}" required></div>
                    <div class="col-12"><label class="form-label">Gambar Produk</label><input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png"><div class="form-text">Biarkan kosong jika mahu kekalkan gambar semasa.</div></div>
                    <div class="col-12"><label class="form-label">Penerangan</label><textarea class="form-control" name="description" rows="4">{{ old('description', $item->description) }}</textarea></div>
                </div>
                <button class="btn btn-danger mt-3">Simpan Perubahan</button>
            </form>
        </div>
    </section>
</div>
@endsection
