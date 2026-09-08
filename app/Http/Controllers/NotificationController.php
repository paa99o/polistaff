<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Mail\PortalNotificationMail;
use App\Models\PortalNotification;
use App\Models\EmailDelivery;
use App\Models\Activity;
use App\Models\ExpenseClaim;
use App\Models\PaymentSubmission;
use App\Models\User;
use App\Mail\ActivityApprovedMail;
use App\Mail\ActivityCancelledMail;
use App\Mail\ExpenseClaimApprovedMail;
use App\Mail\ExpenseClaimRejectedMail;
use App\Mail\ExpenseClaimVerifiedMail;
use App\Mail\FeeReminderMail;
use App\Mail\MembershipApprovedMail;
use App\Mail\MembershipRejectedMail;
use App\Mail\PaymentApprovedMail;
use App\Mail\PaymentRejectedMail;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\EmailAuditService;
use App\Services\EmailDeliveryService;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class NotificationController extends Controller
{
    public function __construct(private EmailAuditService $emailAuditService, private EmailDeliveryService $emailDeliveryService) {}

    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => PortalNotification::where(fn ($query) => $query->where('user_id', $request->user()->id)->orWhereNull('user_id'))->latest()->paginate(15),
        ]);
    }

    public function deliveryMonitor(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('admin.notifications.delivery', [
            'deliveries' => EmailDelivery::with('user', 'notification')->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function retryEmail(Request $request, PortalNotification $notification): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($notification->email_recipient && $notification->email_status === 'failed', 422, 'Hanya email yang gagal boleh dicuba semula.');

        $this->emailDeliveryService->send($notification->user, 'portal notification retry', new PortalNotificationMail($notification), $notification);

        return back()->with('status', 'Email dimasukkan semula ke queue.');
    }

    public function retryDelivery(Request $request, EmailDelivery $delivery): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($delivery->status === 'failed', 422, 'Hanya email yang gagal boleh dicuba semula.');

        if ($delivery->mailable === ResetPasswordNotification::class) {
            $status = Password::sendResetLink(['email' => $delivery->recipient]);
            abort_unless($status === Password::RESET_LINK_SENT, 422, 'Pautan reset tidak dapat dihantar semula.');
        } elseif ($delivery->mailable === VerifyEmailNotification::class) {
            $user = $delivery->user;
            abort_unless($user, 404, 'Penerima email tidak lagi wujud.');
            $user->sendEmailVerificationNotification();
        } else {
            $mailable = $this->retryableMailable($delivery);
            abort_unless($mailable, 422, 'Jenis email ini tidak menyokong retry melalui monitor.');
            $this->emailDeliveryService->send($delivery->user, $delivery->event.' retry', $mailable, $delivery->record);
        }

        return back()->with('status', 'Email dimasukkan semula untuk dihantar.');
    }

    private function retryableMailable(EmailDelivery $delivery): ?\Illuminate\Contracts\Mail\Mailable
    {
        $record = $delivery->record;

        return match ($delivery->mailable) {
            PaymentApprovedMail::class => $record instanceof PaymentSubmission ? new PaymentApprovedMail($record->load('transaction')) : null,
            PaymentRejectedMail::class => $record instanceof PaymentSubmission ? new PaymentRejectedMail($record) : null,
            ExpenseClaimApprovedMail::class => $record instanceof ExpenseClaim ? new ExpenseClaimApprovedMail($record) : null,
            ExpenseClaimRejectedMail::class => $record instanceof ExpenseClaim ? new ExpenseClaimRejectedMail($record) : null,
            ExpenseClaimVerifiedMail::class => $record instanceof ExpenseClaim ? new ExpenseClaimVerifiedMail($record) : null,
            ActivityApprovedMail::class => $record instanceof Activity ? new ActivityApprovedMail($record) : null,
            ActivityCancelledMail::class => $record instanceof Activity ? new ActivityCancelledMail($record) : null,
            MembershipApprovedMail::class => $record instanceof User ? new MembershipApprovedMail($record) : null,
            MembershipRejectedMail::class => $record instanceof User ? new MembershipRejectedMail($record, $record->membership_review_notes ?? 'Permohonan ahli ditolak.') : null,
            FeeReminderMail::class => $record instanceof User ? new FeeReminderMail($record) : null,
            default => null,
        };
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
            $notification = PortalNotification::create([...$notificationData, 'user_id' => $user->id, 'email_recipient' => $user->email]);

            if ($user->email && $user->wantsEmail('announcements')) {
                try {
                    $this->emailDeliveryService->send($user, 'portal notification', new PortalNotificationMail($notification), $notification);
                    $this->emailAuditService->sent($user, 'portal notification', $notification);
                } catch (Throwable $exception) {
                    $notification->update(['email_status' => 'failed', 'email_error' => $exception->getMessage()]);
                }
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
