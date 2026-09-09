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
            <h2>Nak jual sesuatu?</h2>
            <p>Kongsi produk atau servis anda dengan komuniti staf POLISTAFF.</p>
            <a class="btn btn-danger w-100" href="{{ route('polimart.create') }}">Mula Jual</a>
            <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('polimart.index', ['mine' => 1]) }}">
                <i class="bi bi-person-lines-fill me-2" aria-hidden="true"></i>Listing Saya
            </a>
            <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('polimart.favorites') }}">
                <i class="bi bi-heart me-2" aria-hidden="true"></i>Favorite Saya
            </a>
            <a class="btn btn-outline-secondary w-100 mt-2" href="{{ route('polimart.chat.index') }}">
                <i class="bi bi-chat-dots me-2" aria-hidden="true"></i>Chat Saya
            </a>
        </aside>

        <div class="polimart-products">
            <form class="polimart-filter" method="get" action="{{ route('polimart.index') }}">
                <div class="polimart-search-field">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="polimart-search">Cari produk</label>
                    <input id="polimart-search" name="q" type="search" value="{{ $search }}" placeholder="Cari barang, makanan atau servis...">
                </div>
                    <label class="visually-hidden" for="polimart-category">Kategori</label>
                    <select id="polimart-category" name="category" class="form-select">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $itemCategory)
                        <option value="{{ $itemCategory }}" @selected($category === $itemCategory)>{{ $itemCategory }}</option>
                    @endforeach
                    </select>
                    @if($mine)<input type="hidden" name="mine" value="1">@endif
                    @if($favorites)<input type="hidden" name="favorites" value="1">@endif
                <button class="btn btn-danger" type="submit">Cari</button>
                @if($search !== '' || $category !== '' || $mine || $favorites)
                    <a class="btn btn-outline-secondary" href="{{ route('polimart.index') }}">Reset</a>
                @endif
            </form>

            <div class="polimart-section-heading">
                <h2>{{ $mine ? 'Listing Saya' : ($favorites ? 'Favorite Saya' : ($search !== '' || $category !== '' ? 'Hasil Carian' : 'Produk Staf')) }}</h2>
                <span class="badge">{{ $items->total() }} listing</span>
            </div>

            <div class="polimart-grid">
                @forelse($items as $item)
                    <article class="polimart-item">
                        <a class="polimart-item-media" href="{{ route('polimart.show', $item) }}">
                            @if($item->image_path)
                                <img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->name }}">
                            @else
                                <div class="polimart-item-placeholder">
                                    <i class="bi bi-bag-heart" aria-hidden="true"></i>
                                </div>
                            @endif
                        </a>
                        <div class="polimart-item-body">
                            <div class="d-flex justify-content-between gap-3 align-items-start">
                                <div>
                                    <span class="polimart-category">{{ $item->category }}</span>
                                    <h3><a href="{{ route('polimart.show', $item) }}">{{ $item->name }}</a></h3>
                                </div>
                                <strong class="polimart-price">RM {{ number_format((float) $item->price, 2) }}</strong>
                            </div>
                            @if(in_array($item->id, $favoriteIds, true))
                                <span class="polimart-favorite-label"><i class="bi bi-heart-fill me-1" aria-hidden="true"></i>Disimpan</span>
                            @endif
                            @if($mine)
                                <span class="polimart-status polimart-status-{{ $item->status }} align-self-start">{{ ucfirst($item->status) }}</span>
                            @endif
                            <p>{{ $item->description ?: 'Tiada penerangan tambahan.' }}</p>
                            <div class="polimart-seller">
                                <a href="{{ route('polimart.seller', $item->user) }}"><i class="bi bi-person" aria-hidden="true"></i>{{ $item->user->name }}</a>
                                <span><i class="bi bi-telephone" aria-hidden="true"></i>{{ $item->contact }}</span>
                            </div>
                            @if($item->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                                <div class="d-flex gap-2 mt-3">
                                    <a class="btn btn-sm btn-outline-secondary flex-fill" href="{{ route('polimart.edit', $item) }}">Edit</a>
                                    <a class="btn btn-sm btn-danger flex-fill" href="{{ route('polimart.show', $item) }}">Urus</a>
                                </div>
                                <form method="post" action="{{ route('polimart.destroy', $item) }}">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-outline-danger w-100 mt-3">Padam Listing</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="polimart-empty">
                        <i class="bi bi-shop" aria-hidden="true"></i>
                        @if($mine)
                            <p>Anda belum ada listing. Mula jual barang pertama anda.</p>
                            <a class="btn btn-danger" href="{{ route('polimart.create') }}">Jual Barang</a>
                        @elseif($favorites)
                            <p>Anda belum simpan mana-mana listing.</p>
                            <a class="btn btn-outline-danger" href="{{ route('polimart.index') }}">Cari Barang</a>
                        @elseif($search !== '' || $category !== '')
                            <p>Tiada barang sepadan dengan carian anda.</p>
                            <a class="btn btn-outline-danger" href="{{ route('polimart.index') }}">Lihat Semua Listing</a>
                        @else
                            <p>Belum ada produk. Jadilah seller pertama di PoliMart.</p>
                        @endif
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $items->links() }}</div>
        </div>
    </section>
</div>
@endsection
