<?php

namespace App\Http\Controllers;

use App\Models\PolimartConversation;
use App\Models\PolimartItem;
use App\Models\PortalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolimartChatController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = PolimartConversation::with(['item', 'buyer', 'seller', 'latestMessage'])
            ->withCount(['messages as unread_messages_count' => fn ($query) => $query
                ->where('sender_id', '!=', $request->user()->id)
                ->whereNull('read_at')])
            ->where(fn ($query) => $query
                ->where('buyer_id', $request->user()->id)
                ->orWhere('seller_id', $request->user()->id))
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('polimart.chat.index', compact('conversations'));
    }

    public function start(Request $request, PolimartItem $polimartItem): RedirectResponse
    {
        abort_unless(in_array($polimartItem->status, ['active', 'reserved'], true), 404);
        abort_if($polimartItem->user_id === $request->user()->id, 403);

        $conversation = PolimartConversation::firstOrCreate([
            'polimart_item_id' => $polimartItem->id,
            'buyer_id' => $request->user()->id,
        ], [
            'seller_id' => $polimartItem->user_id,
        ]);

        return redirect()->route('polimart.chat.show', $conversation);
    }

    public function show(Request $request, PolimartConversation $conversation): View
    {
        $this->authorizeParticipant($request, $conversation);
        $conversation->load(['item', 'buyer', 'seller', 'messages.sender']);
        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('polimart.chat.show', compact('conversation'));
    }

    public function send(Request $request, PolimartConversation $conversation): RedirectResponse
    {
        $this->authorizeParticipant($request, $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        $conversation->update(['last_message_at' => now()]);

        $recipientId = $request->user()->id === $conversation->buyer_id
            ? $conversation->seller_id
            : $conversation->buyer_id;

        PortalNotification::create([
            'user_id' => $recipientId,
            'title' => 'Mesej PoliMart baharu',
            'message' => $request->user()->name.' menghantar mesej tentang '.$conversation->item()->value('name').': '.str($message->body)->limit(100),
            'type' => 'polimart_chat',
            'link' => route('polimart.chat.show', $conversation),
        ]);

        return back()->with('status', 'Mesej dihantar.');
    }

    private function authorizeParticipant(Request $request, PolimartConversation $conversation): void
    {
        abort_unless(in_array($request->user()->id, [$conversation->buyer_id, $conversation->seller_id], true), 403);
    }
}
