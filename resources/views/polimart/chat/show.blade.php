@extends('layouts.app', ['title' => 'Chat · '.$conversation->item->name])

@section('content')
<div class="polimart-page">
    <section class="polimart-form-shell">
        <a class="btn btn-light polimart-back-button mb-4" href="{{ route('polimart.chat.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Semua Chat</a>
        <div class="polimart-chat-shell">
            <div class="polimart-chat-header">
                <div>
                    <span class="polimart-category">{{ $conversation->item->category }}</span>
                    <h1>{{ $conversation->item->name }}</h1>
                    <p>{{ $conversation->buyer_id === auth()->id() ? 'Seller: '.$conversation->seller->name : 'Buyer: '.$conversation->buyer->name }}</p>
                </div>
                <a class="btn btn-outline-danger" href="{{ route('polimart.show', $conversation->item) }}">Lihat Listing</a>
            </div>

            <div class="polimart-messages">
                @forelse($conversation->messages as $message)
                    <div class="polimart-message {{ $message->sender_id === auth()->id() ? 'is-mine' : '' }}">
                        <span>{{ $message->body }}</span>
                        <small>{{ $message->created_at->format('d/m/Y h:i A') }}</small>
                    </div>
                @empty
                    <div class="polimart-chat-empty">Mulakan perbualan untuk bincang harga, lokasi pickup atau penghantaran.</div>
                @endforelse
            </div>

            <form class="polimart-message-form" method="post" action="{{ route('polimart.chat.send', $conversation) }}">
                @csrf
                <label class="visually-hidden" for="message-body">Mesej</label>
                <textarea id="message-body" name="body" rows="2" maxlength="2000" placeholder="Tulis mesej anda..." required></textarea>
                <button class="btn btn-danger" type="submit"><i class="bi bi-send me-2" aria-hidden="true"></i>Hantar</button>
            </form>
        </div>
    </section>
</div>
@endsection
