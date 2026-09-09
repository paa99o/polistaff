@extends('layouts.app', ['title' => $seller->name.' · PoliMart'])

@section('content')
<div class="polimart-page">
    <section class="polimart-form-shell">
        <a class="btn btn-light polimart-back-button mb-4" href="{{ route('polimart.index') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke PoliMart
        </a>

        <div class="polimart-seller-hero">
            <span class="stat-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
            <div>
                <span class="polimart-kicker">Penjual PoliMart</span>
                <h1>{{ $seller->name }}</h1>
                <p>{{ $items->total() }} listing aktif</p>
            </div>
        </div>

        <div class="polimart-section-heading mt-5">
            <h2>Produk Penjual</h2>
        </div>
        <div class="polimart-grid">
            @forelse($items as $item)
                <article class="polimart-item">
                    <a class="polimart-item-media" href="{{ route('polimart.show', $item) }}">
                        @if($item->image_path)
                            <img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->name }}">
                        @else
                            <div class="polimart-item-placeholder"><i class="bi bi-bag-heart" aria-hidden="true"></i></div>
                        @endif
                    </a>
                    <div class="polimart-item-body">
                        <span class="polimart-category">{{ $item->category }}</span>
                        <div class="d-flex justify-content-between gap-3 align-items-start">
                            <h3><a href="{{ route('polimart.show', $item) }}">{{ $item->name }}</a></h3>
                            <strong class="polimart-price">RM {{ number_format((float) $item->price, 2) }}</strong>
                        </div>
                        <p>{{ $item->description ?: 'Tiada penerangan tambahan.' }}</p>
                    </div>
                </article>
            @empty
                <div class="polimart-empty"><i class="bi bi-shop" aria-hidden="true"></i><p>Seller ini belum ada listing aktif.</p></div>
            @endforelse
        </div>
        <div class="mt-4">{{ $items->links() }}</div>
    </section>
</div>
@endsection
