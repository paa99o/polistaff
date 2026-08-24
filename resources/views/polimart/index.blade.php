@extends('layouts.app', ['title' => 'PoliMart'])

@section('content')
<div class="polimart-page">
    <section class="polimart-hero">
        <div>
            <div class="auth-stripe polimart-stripe mb-3" aria-hidden="true"><span></span><span></span><span></span></div>
            <p class="polimart-kicker">Pasar komuniti staf</p>
            <h1 class="polimart-title" aria-label="POLIMART">
                <span>POLI</span><span>MART</span>
            </h1>
            <p>Ruang khas untuk staf menjual produk, makanan, servis kecil atau barangan pre-loved kepada komuniti POLISTAFF.</p>
        </div>
    </section>

    <section class="polimart-market">
        <aside class="polimart-start-card">
            <span class="stat-icon"><i class="bi bi-bag-plus" aria-hidden="true"></i></span>
            <h2>Start listing your product?</h2>
            <p>Kongsi produk atau servis anda dengan komuniti staf POLISTAFF.</p>
            <a class="btn btn-danger w-100" href="{{ route('polimart.create') }}">Lets Get Start</a>
        </aside>

        <div class="polimart-products">
            <div class="polimart-section-heading">
                <h2>Produk Staf</h2>
                <span class="badge">{{ $items->total() }} listing</span>
            </div>

            <div class="polimart-grid">
                @forelse($items as $item)
                    <article class="polimart-item">
                        @if($item->image_path)
                            <img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->name }}">
                        @else
                            <div class="polimart-item-placeholder">
                                <i class="bi bi-bag-heart" aria-hidden="true"></i>
                            </div>
                        @endif
                        <div class="polimart-item-body">
                            <div class="d-flex justify-content-between gap-3 align-items-start">
                                <div>
                                    <span class="polimart-category">{{ $item->category }}</span>
                                    <h3>{{ $item->name }}</h3>
                                </div>
                                <strong class="polimart-price">RM {{ number_format((float) $item->price, 2) }}</strong>
                            </div>
                            <p>{{ $item->description ?: 'Tiada penerangan tambahan.' }}</p>
                            <div class="polimart-seller">
                                <span><i class="bi bi-person" aria-hidden="true"></i>{{ $item->user->name }}</span>
                                <span><i class="bi bi-telephone" aria-hidden="true"></i>{{ $item->contact }}</span>
                            </div>
                            @if($item->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                                <form method="post" action="{{ route('polimart.destroy', $item) }}">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-outline-danger w-100 mt-3">Delete Listing</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="polimart-empty">
                        <i class="bi bi-shop" aria-hidden="true"></i>
                        <p>Belum ada produk. Jadilah seller pertama di PoliMart.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $items->links() }}</div>
        </div>
    </section>
</div>
@endsection
