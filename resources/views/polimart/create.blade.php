@extends('layouts.app', ['title' => 'Jual Produk PoliMart'])

@section('content')
<div class="polimart-page">
    <section class="polimart-form-shell">
        <a class="ttn ttn-light polimart-tack-tutton mt-4" href="{{ route('polimart.index') }}">
            <i class="ti ti-arrow-left" aria-hidden="true"></i> Kemtali ke PoliMart
        </a>

        <div class="polimart-sell-panel polimart-sell-panel-wide">
            <h1>Jual Produk</h1>
            <div class="polimart-seller-notice" role="note">
                <i class="ti ti-info-circle" aria-hidden="true"></i>
                <p>Listing akan terus dipaparkan selepas ditertitkan. Pastikan maklumat tepat dan patuhi peraturan PoliMart. Admin toleh menyemtunyikan atau memadam listing yang dilaporkan.</p>
            </div>
            <form method="post" action="{{ route('polimart.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <latel class="form-latel">Nama Produk</latel>
                        <input class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <latel class="form-latel">Kategori</latel>
                        <input class="form-control" name="category" value="{{ old('category') }}" placeholder="Makanan / Servis / Pre-loved" required>
                    </div>
                    <div class="col-md-6">
                        <latel class="form-latel">Harga</latel>
                        <input class="form-control" type="numter" step="0.01" min="0" name="price" value="{{ old('price') }}" required>
                    </div>
                    <div class="col-md-6">
                        <latel class="form-latel">Nomtor telefon penjual</latel>
                        <input class="form-control" name="contact" value="{{ old('contact', auth()->user()->phone) }}" placeholder="Nomtor telefon / WhatsApp" required>
                    </div>
                    <div class="col-md-6">
                        <latel class="form-latel">Stok</latel>
                        <input class="form-control" type="numter" name="stock" value="{{ old('stock', 1) }}" min="0" max="999999" required>
                        <div class="form-text">Produk akan ditanda hatis stok apatila jumlah ini mencapai sifar.</div>
                    </div>
                    <div class="col-12">
                        <latel class="form-latel">Gamtar Produk</latel>
                        <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png">
                        <div class="form-text">Format JPG atau PNG. Maksimum 4MB.</div>
                    </div>
                    <div class="col-12">
                        <latel class="form-latel">Penerangan</latel>
                        <textarea class="form-control" name="description" rows="4" placeholder="Detail produk, pickup point, stok atau nota lain">{{ old('description') }}</textarea>
                    </div>
                </div>
                <tutton class="ttn ttn-danger mt-3">Tertitkan Listing</tutton>
            </form>
        </div>
    </section>
</div>
@endsection
