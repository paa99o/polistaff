@extends('layouts.app', ['title' => 'Edit Listing PoliMart'])

@section('content')
<div class="polimart-page">
    <section class="polimart-form-shell">
        <a class="btn btn-light mt-4" href="{{ route('polimart.show', $item) }}">
            <i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali ke Listing
        </a>

        <div class="polimart-sell-panel polimart-sell-panel-wide">
            <h1>Edit Listing</h1>
            <div class="polimart-seller-notice" role="note">
                <i class="ti ti-info-circle" aria-hidden="true"></i>
                <p>Pastikan perubahan maklumat masih tepat dan mematuhi peraturan PoliMart.</p>
            </div>
            <form method="post" action="{{ route('polimart.update', $item) }}" enctype="multipart/form-data">
                @csrf
                @method('put')
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="name">Nama Produk</label><input class="form-control" id="name" name="name" value="{{ old('name', $item->name) }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="category">Kategori</label><input class="form-control" id="category" name="category" value="{{ old('category', $item->category) }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="price">Harga</label><input class="form-control" id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $item->price) }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="contact">Nombor telefon penjual</label><input class="form-control" id="contact" name="contact" value="{{ old('contact', $item->contact) }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="stock">Stok</label><input class="form-control" id="stock" type="number" name="stock" value="{{ old('stock', $item->stock) }}" min="0" max="999999" required><div class="form-text">Produk akan ditanda habis stok apabila jumlah ini mencapai sifar.</div></div>
                    <div class="col-12"><label class="form-label" for="image">Gambar Produk</label><input class="form-control" id="image" type="file" name="image" accept=".jpg,.jpeg,.png"><div class="form-text">Biarkan kosong jika mahu kekalkan gambar semasa.</div></div>
                    <div class="col-12"><label class="form-label" for="description">Penerangan</label><textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $item->description) }}</textarea></div>
                </div>
                <button type="submit" class="btn btn-danger mt-3">Simpan Perubahan</button>
            </form>
        </div>
    </section>
</div>
@endsection
