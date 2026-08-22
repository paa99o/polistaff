<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => PortalNotification::where(fn ($query) => $query->where('user_id', $request->user()->id)->orWhereNull('user_id'))->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('notifications.create', ['users' => User::orderBy('name')->get()]);
    }

    public function store(NotificationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('send_to_all')) {
            PortalNotification::create([...$data, 'user_id' => null]);
        } else {
            PortalNotification::create($data);
        }

        return redirect()->route('notifications.index')->with('status', 'Notifikasi dihantar.');
    }

    public function markRead(PortalNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === null || $notification->user_id === auth()->id(), 403);
        $notification->update(['is_read' => true]);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        PortalNotification::where('user_id', $request->user()->id)->where('is_read', false)->update(['is_read' => true]);

        return back()->with('status', 'Semua notifikasi ditanda sebagai dibaca.');
    }
}
