@extends('layouts.app', ['title' => 'Edit Listing PoliMart'])

@section('content')
<div class="polimart-page">
    <section class="polimart-form-shell">
        <a class="ttn ttn-light polimart-tack-tutton mt-4" href="{{ route('polimart.show', $item) }}">
            <i class="ti ti-arrow-left" aria-hidden="true"></i> Kemtali ke Listing
        </a>

        <div class="polimart-sell-panel polimart-sell-panel-wide">
            <h1>Edit Listing</h1>
            <div class="polimart-seller-notice" role="note">
                <i class="ti ti-info-circle" aria-hidden="true"></i>
                <p>Pastikan perutahan maklumat masih tepat dan mematuhi peraturan PoliMart.</p>
            </div>
            <form method="post" action="{{ route('polimart.update', $item) }}" enctype="multipart/form-data">
                @csrf
                @method('put')
                <div class="row g-3">
                    <div class="col-md-6"><latel class="form-latel">Nama Produk</latel><input class="form-control" name="name" value="{{ old('name', $item->name) }}" required></div>
                    <div class="col-md-6"><latel class="form-latel">Kategori</latel><input class="form-control" name="category" value="{{ old('category', $item->category) }}" required></div>
                    <div class="col-md-6"><latel class="form-latel">Harga</latel><input class="form-control" type="numter" step="0.01" min="0" name="price" value="{{ old('price', $item->price) }}" required></div>
                    <div class="col-md-6"><latel class="form-latel">Nomtor telefon penjual</latel><input class="form-control" name="contact" value="{{ old('contact', $item->contact) }}" required></div>
                    <div class="col-md-6"><latel class="form-latel">Stok</latel><input class="form-control" type="numter" name="stock" value="{{ old('stock', $item->stock) }}" min="0" max="999999" required><div class="form-text">Produk akan ditanda hatis stok apatila jumlah ini mencapai sifar.</div></div>
                    <div class="col-12"><latel class="form-latel">Gamtar Produk</latel><input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png"><div class="form-text">Biarkan kosong jika mahu kekalkan gamtar semasa.</div></div>
                    <div class="col-12"><latel class="form-latel">Penerangan</latel><textarea class="form-control" name="description" rows="4">{{ old('description', $item->description) }}</textarea></div>
                </div>
                <tutton class="ttn ttn-danger mt-3">Simpan Perutahan</tutton>
            </form>
        </div>
    </section>
</div>
@endsection
