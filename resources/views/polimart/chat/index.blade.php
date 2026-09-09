@extends('layouts.app', ['title' => 'Chat PoliMart'])

@section('content')
<div class="polimart-page">
    <section class="polimart-form-shell">
        <div class="polimart-chat-heading">
            <div>
                <p class="polimart-kicker">Urusan jual beli</p>
                <h1>Chat PoliMart</h1>
                <p>Berbincang dengan buyer atau seller sebelum confirm pembelian.</p>
            </div>
            <a class="btn btn-light polimart-back-button" href="{{ route('polimart.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>PoliMart</a>
        </div>

        <div class="polimart-chat-list">
            @forelse($conversations as $conversation)
                @php($otherUser = $conversation->buyer_id === auth()->id() ? $conversation->seller : $conversation->buyer)
                <a class="polimart-chat-list-item" href="{{ route('polimart.chat.show', $conversation) }}">
                    <span class="stat-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                    <span class="polimart-chat-list-copy">
                        <strong>{{ $conversation->item->name }}</strong>
                        <small>{{ $otherUser->name }} · {{ ucfirst($conversation->item->status) }}</small>
                        <span>{{ $conversation->latestMessage ? str($conversation->latestMessage->body)->limit(72) : 'Belum ada mesej.' }}</span>
                    </span>
                    <small class="polimart-chat-time">{{ $conversation->last_message_at?->diffForHumans() }}</small>
                    @if($conversation->unread_messages_count > 0)
                        <span class="badge bg-danger">{{ $conversation->unread_messages_count }}</span>
                    @endif
                </a>
            @empty
                <div class="polimart-empty"><i class="bi bi-chat-square-text" aria-hidden="true"></i><p>Belum ada perbualan. Tekan Chat Seller pada listing yang anda minat.</p><a class="btn btn-danger" href="{{ route('polimart.index') }}">Cari Barang</a></div>
            @endforelse
        </div>
        <div class="mt-4">{{ $conversations->links() }}</div>
    </section>
</div>
@endsection
