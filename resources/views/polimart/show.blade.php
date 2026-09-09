@extends('layouts.app', ['title' => $item->name])

@section('content')
<div class="polimart-page polimart-detail-page">
    <section class="polimart-detail-shell">
        <a class="polimart-detail-back" href="{{ route('polimart.index') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke PoliMart
        </a>

        <div class="polimart-detail-gallery">
            @if($item->image_path)
                <img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->name }}">
            @else
                <div class="polimart-detail-placeholder"><i class="bi bi-bag-heart" aria-hidden="true"></i><span>Tiada gambar produk</span></div>
            @endif
        </div>

        <div class="polimart-detail-layout">
            <main class="polimart-detail-main">
                <div class="polimart-detail-heading">
                    <div>
                        <span class="polimart-category">{{ $item->category }}</span>
                        <h1>{{ $item->name }}</h1>
                    </div>
                    <strong class="polimart-detail-price">RM {{ number_format((float) $item->price, 2) }}</strong>
                </div>

                <div class="polimart-detail-actions">
                    @if(in_array($item->status, ['active', 'reserved'], true) && $item->user_id !== auth()->id())
                        <form method="post" action="{{ route('polimart.chat.start', $item) }}">
                            @csrf
                            <button class="btn btn-danger" type="submit"><i class="bi bi-chat-dots me-2" aria-hidden="true"></i>Chat Seller</button>
                        </form>
                    @endif
                    @if($item->status === 'active')
                        <form method="post" action="{{ route('polimart.favorite', $item) }}">
                            @csrf
                            <button class="btn {{ $isFavorited ? 'btn-danger' : 'btn-outline-danger' }}" type="submit"><i class="bi {{ $isFavorited ? 'bi-heart-fill' : 'bi-heart' }} me-2" aria-hidden="true"></i>{{ $isFavorited ? 'Disimpan' : 'Simpan' }}</button>
                        </form>
                    @endif
                    <span class="polimart-status polimart-status-{{ $item->status }}">{{ ucfirst($item->status) }}</span>
                </div>

                <section class="polimart-detail-section">
                    <h2>Butiran</h2>
                    <dl class="polimart-detail-facts">
                        <div><dt>Kategori</dt><dd>{{ $item->category }}</dd></div>
                        <div><dt>Diterbitkan</dt><dd>{{ $item->created_at->diffForHumans() }}</dd></div>
                        <div><dt>Penjual</dt><dd><a href="{{ route('polimart.seller', $item->user) }}">{{ $item->user->name }}</a></dd></div>
                        <div><dt>Status</dt><dd>{{ ucfirst($item->status) }}</dd></div>
                    </dl>
                </section>

                <section class="polimart-detail-section">
                    <h2>Penerangan</h2>
                    <p class="polimart-detail-description">{{ $item->description ?: 'Tiada penerangan tambahan.' }}</p>
                </section>

                @if($item->status === 'sold' && $item->user_id !== auth()->id())
                    <section class="polimart-review-box">
                        <h2>Review barang</h2>
                        <form method="post" action="{{ route('polimart.review', $item) }}">
                            @csrf
                            <label class="form-label" for="rating">Rating</label>
                            <select class="form-select mb-2" id="rating" name="rating" required>
                                <option value="">Pilih rating</option>
                                @foreach(range(5, 1) as $rating)<option value="{{ $rating }}">{{ $rating }}/5</option>@endforeach
                            </select>
                            <textarea class="form-control mb-2" name="comment" rows="3" maxlength="1000" placeholder="Kongsi pengalaman anda (optional)"></textarea>
                            <button class="btn btn-outline-danger btn-sm" type="submit">Hantar Review</button>
                        </form>
                    </section>
                @endif

                @if($item->reviews->isNotEmpty())
                    <section class="polimart-reviews">
                        <h2>Review ({{ $item->reviews->count() }})</h2>
                        @foreach($item->reviews as $review)
                            <div class="polimart-review">
                                <strong>{{ $review->user->name }}</strong>
                                <span class="polimart-stars">{{ $review->rating }}/5</span>
                                @if($review->comment)<p>{{ $review->comment }}</p>@endif
                            </div>
                        @endforeach
                    </section>
                @endif
            </main>

            <aside class="polimart-detail-sidebar">
                <div class="polimart-seller-card">
                    <div class="polimart-seller-avatar">{{ mb_strtoupper(mb_substr($item->user->name, 0, 1)) }}</div>
                    <div>
                        <span class="polimart-category">Penjual PoliMart</span>
                        <a class="polimart-seller-name" href="{{ route('polimart.seller', $item->user) }}">{{ $item->user->name }}</a>
                    </div>
                    <p><i class="bi bi-shield-check me-2" aria-hidden="true"></i>Urusan pembelian melalui chat PoliMart.</p>
                    @if(in_array($item->status, ['active', 'reserved'], true) && $item->user_id !== auth()->id())
                        <form method="post" action="{{ route('polimart.chat.start', $item) }}">
                            @csrf
                            <button class="btn btn-danger w-100" type="submit"><i class="bi bi-chat-dots me-2" aria-hidden="true"></i>Mulakan Chat</button>
                        </form>
                    @endif
                </div>

                @if($item->user_id === auth()->id() || auth()->user()->hasRole('admin'))
                    <div class="polimart-owner-actions">
                        <a class="btn btn-outline-secondary" href="{{ route('polimart.edit', $item) }}">Edit Listing</a>
                        <form method="post" action="{{ route('polimart.status', $item) }}" class="d-flex gap-2 flex-wrap">
                            @csrf
                            @method('patch')
                            <select class="form-select" name="status" aria-label="Status listing">
                                @foreach(['active' => 'Available', 'reserved' => 'Reserved', 'sold' => 'Sold'] as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" @selected($item->status === $statusValue)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-danger" type="submit">Simpan Status</button>
                        </form>
                    </div>
                @elseif($item->status === 'active')
                    <details class="polimart-report-box">
                        <summary><i class="bi bi-flag me-2" aria-hidden="true"></i>Laporkan listing</summary>
                        <form method="post" action="{{ route('polimart.report', $item) }}" class="mt-3">
                            @csrf
                            <select class="form-select mb-2" name="reason" required>
                                <option value="">Pilih sebab</option>
                                <option value="scam">Disyaki scam</option>
                                <option value="prohibited">Barang tidak dibenarkan</option>
                                <option value="misleading">Maklumat mengelirukan</option>
                                <option value="duplicate">Listing berulang</option>
                                <option value="other">Lain-lain</option>
                            </select>
                            <textarea class="form-control mb-2" name="details" rows="3" maxlength="1000" placeholder="Nota tambahan (optional)"></textarea>
                            <button class="btn btn-outline-danger btn-sm" type="submit">Hantar Laporan</button>
                        </form>
                    </details>
                @endif
            </aside>
        </div>
    </section>
</div>
@endsection
