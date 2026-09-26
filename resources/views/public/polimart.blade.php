@extends('layouts.public', ['title' => 'PoliMart'])

@section('content')
<section class="public-container public-listing-hero public-market-hero">
    <p class="public-eyebrow">Kedai komuniti POLIBEST</p>
    <h1>PoliMart</h1>
    <p>Jelajah barangan, makanan dan servis daripada komuniti POLIBEST. Tidak perlu daftar untuk melihat produk dan membuat pembelian.</p>
</section>

<section id="produk" class="public-container public-store-grid-section">
    <form class="public-store-filter" method="get" action="{{ route('polimart.index') }}">
        <div class="public-search-field"><i class="bi bi-search" aria-hidden="true"></i><label class="visually-hidden" for="public-polimart-search">Cari produk</label><input id="public-polimart-search" name="q" type="search" value="{{ $search }}" placeholder="Cari nama produk..."></div>
        <select name="category" class="form-select" aria-label="Kategori"><option value="">Semua kategori</option>@foreach($categories as $itemCategory)<option value="{{ $itemCategory }}" @selected($category === $itemCategory)>{{ $itemCategory }}</option>@endforeach</select>
        <button class="btn btn-primary" type="submit">Cari</button>
    </form>

    <div class="public-store-heading"><div><p class="public-eyebrow">Produk tersedia</p><h2>Jualan komuniti</h2></div><span class="badge">{{ $items->total() }} produk</span></div>
    <div class="public-store-products">
        @forelse($items as $item)
            <article class="public-product-card">
                <a class="public-product-media" href="{{ route('polimart.show', $item) }}">@if($item->image_path)<img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->name }}">@else<i class="bi bi-bag-heart" aria-hidden="true"></i>@endif</a>
                <div class="public-product-body"><span class="public-product-category">{{ $item->category }}</span><h3><a href="{{ route('polimart.show', $item) }}">{{ $item->name }}</a></h3><strong>RM {{ number_format((float) $item->price, 2) }}</strong><p>{{ $item->description ?: 'Tiada penerangan tambahan.' }}</p><div class="public-product-seller"><span>{{ $item->user->name }}</span><span class="{{ $item->stock > 0 ? 'text-success' : 'text-danger' }}"><i class="bi bi-box-seam me-1" aria-hidden="true"></i>{{ $item->stock > 0 ? $item->stock.' stok tinggal' : 'Habis stok' }}</span><a class="btn btn-primary btn-sm" href="{{ route('polimart.show', $item) }}">Lihat Produk</a></div></div>
            </article>
        @empty
            <div class="public-empty-state"><i class="bi bi-shop" aria-hidden="true"></i><h2>Tiada produk ditemui.</h2><p>Cuba ubah carian atau kategori anda.</p></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</section>
@endsection
