<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Mail\PortalNotificationMail;
use App\Models\PortalNotification;
use App\Models\User;
use App\Services\EmailAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private EmailAuditService $emailAuditService) {}

    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => PortalNotification::where(fn ($query) => $query->where('user_id', $request->user()->id)->orWhereNull('user_id'))->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('notifications.create', [
            'departments' => User::whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
            'roles' => ['member' => 'Member', 'treasurer' => 'Treasurer', 'chairman' => 'Chairman', 'admin' => 'Admin'],
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(NotificationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $notificationData = collect($data)->only(['title', 'message', 'type', 'link'])->all();

        $users = $this->targetUsers($data);

        foreach ($users as $user) {
            $notification = PortalNotification::create([...$notificationData, 'user_id' => $user->id]);

            if ($user->email && $user->wantsEmail('announcements')) {
                Mail::to($user->email)->send(new PortalNotificationMail($notification));
                $this->emailAuditService->sent($user, 'portal notification', $notification);
            }
        }

        return redirect()->route('notifications.index')->with('status', 'Notifikasi dihantar kepada '.$users->count().' penerima. Emel diproses mengikut tetapan penerima.');
    }

    private function targetUsers(array $data)
    {
        $query = User::query()->orderBy('id');

        if ($data['target'] === 'all_active') {
            $query->where('membership_status', 'active');
        }

        if ($data['target'] === 'department') {
            $query->where('membership_status', 'active')->where('department', $data['department']);
        }

        if ($data['target'] === 'role') {
            $query->where('membership_status', 'active')->where('role', $data['role']);
        }

        if ($data['target'] === 'individual') {
            $query->where('id', $data['user_id']);
        }

        return $query->get();
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
