<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityRequest;
use App\Mail\ActivityApprovedMail;
use App\Mail\ActivityCancelledMail;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\PortalNotification;
use App\Models\User;
use App\Services\EmailAuditService;
use App\Services\EmailDeliveryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(private EmailAuditService $emailAuditService, private EmailDeliveryService $emailDeliveryService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));
        $calendarMonth = preg_match('/^\d{4}-\d{2}$/', $month)
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();
        $calendarStart = $calendarMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $calendarActivities = Activity::query()
            ->when(! $user->hasRole('admin', 'treasurer'), fn ($query) => $query->where(function ($q) use ($user) { $q->where('status', 'approved')->orWhere('created_by', $user->id); }))
            ->whereBetween('date_time', [$calendarStart, $calendarEnd])
            ->orderBy('date_time')
            ->get()
            ->groupBy(fn (Activity $activity) => $activity->date_time->toDateString());

        $calendarWeeks = [];
        for ($weekStart = $calendarStart->copy(); $weekStart->lessThanOrEqualTo($calendarEnd); $weekStart->addWeek()) {
            $week = [];
            for ($offset = 0; $offset < 7; $offset++) {
                $date = $weekStart->copy()->addDays($offset);
                $week[] = [
                    'date' => $date,
                    'activities' => $calendarActivities->get($date->toDateString(), collect()),
                ];
            }
            $calendarWeeks[] = $week;
        }

        $activities = Activity::query()
            ->when(! $user->hasRole('admin', 'treasurer'), fn ($query) => $query->where(function ($q) use ($user) { $q->where('status', 'approved')->orWhere('created_by', $user->id); }))
            ->when(in_array($request->input('status'), ['approved', 'rejected', 'pending_approval', 'treasurer_verified'], true), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('date_time', $request->date))
            ->orderBy('date_time')
            ->paginate(10)
            ->withQueryString();

        $scope = $user->hasRole('admin', 'treasurer') ? Activity::query() : Activity::where('created_by', $user->id);
        $stats = [
            'approved' => (clone $scope)->where('status', 'approved')->count(),
            'rejected' => (clone $scope)->where('status', 'rejected')->count(),
            'pending' => (clone $scope)->where('status', 'pending_approval')->count(),
            'treasurer_verified' => (clone $scope)->where('status', 'treasurer_verified')->count(),
        ];

        return view('activities.index', compact('activities', 'calendarMonth', 'calendarWeeks', 'stats'));
    }

    public function publicIndex(Request $request): View
    {
        $view = $request->query('view') === 'past' ? 'past' : 'upcoming';
        $activities = Activity::query()
            ->where('status', 'approved')
            ->when($view === 'past', fn ($query) => $query->where('date_time', '<', now())->latest('date_time'))
            ->when($view === 'upcoming', fn ($query) => $query->where('date_time', '>=', now())->oldest('date_time'))
            ->paginate(12)
            ->withQueryString();

        return view('public.activities', compact('activities', 'view'));
    }

    public function publicShow(Activity $activity): View
    {
        abort_unless($activity->status === 'approved', 404);

        return view('public.activity-show', compact('activity'));
    }

    public function create(): View
    {
        Gate::authorize('manage-activities');

        return view('activities.create');
    }

    public function store(ActivityRequest $request): RedirectResponse
    {
        Gate::authorize('manage-activities');

        $data = $request->validated();
        unset($data['evidence_photo']);

        if ($request->hasFile('evidence_photo')) {
            $data['evidence_photo_path'] = $request->file('evidence_photo')->store('activity-evidence', 'public');
        }

        $data['status'] = 'pending_approval';
        $data['created_by'] = $request->user()->id;
        $data['attendance_opens_at'] = $data['date_time'];
        $data['end_time'] = Carbon::parse($data['date_time'])->setTimeFromTimeString($request->input('end_time'));
        abort_if($data['end_time']->lessThanOrEqualTo(Carbon::parse($data['date_time'])), 422, 'Masa berakhir mesti selepas masa bermula.');
        $data['attendance_closes_at'] = $data['end_time'];
        $activity = Activity::create([...$data, 'qr_code_token' => Str::uuid()->toString()]);

        User::where('role', 'treasurer')->where('membership_status', 'active')->get()->each(function (User $user) use ($activity): void {
            PortalNotification::create(['user_id' => $user->id, 'title' => 'Permohonan aktiviti baharu', 'message' => $activity->title.' menunggu kelulusan bendahari.', 'type' => 'info', 'link' => route('activities.show', $activity)]);
        });

        return redirect()->route('activities.show', $activity)->with('status', 'Aktiviti berjaya dicipta.');
    }

    public function show(Activity $activity): View
    {
        $user = auth()->user();
        $canManageAttendance = $user?->hasRole('admin', 'treasurer') ?? false;

        abort_unless($activity->status === 'approved' || $activity->created_by === $user?->id || $user?->hasRole('admin', 'treasurer'), 404);

        $activity->loadCount(['attendances', 'activeRegistrations', 'waitlistedRegistrations']);
        $registration = $user ? $activity->registrations()->where('user_id', $user->id)->first() : null;

        if ($canManageAttendance) {
            $activity->load('attendances.user', 'activeRegistrations.user', 'waitlistedRegistrations.user', 'guestRegistrations');
        }

        return view('activities.show', [
            'activity' => $activity,
            'registration' => $registration,
            'canManageAttendance' => $canManageAttendance,
            'timelineLogs' => $this->timelineLogs($activity),
        ]);
    }

    public function edit(Activity $activity): View
    {
        Gate::authorize('manage-activities');
        abort_unless($activity->created_by === auth()->id() && $activity->status === 'pending_approval', 403);

        return view('activities.edit', compact('activity'));
    }

    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        Gate::authorize('manage-activities');
        abort_unless($activity->created_by === auth()->id() && $activity->status === 'pending_approval', 403);

        $beforeStatus = $activity->status;
        $data = $request->validated();
        unset($data['evidence_photo']);

        if ($request->hasFile('evidence_photo')) {
            if ($activity->evidence_photo_path) {
                Storage::disk('public')->delete($activity->evidence_photo_path);
            }

            $data['evidence_photo_path'] = $request->file('evidence_photo')->store('activity-evidence', 'public');
        }

        $activity->update($data);

        if ($beforeStatus !== 'cancelled' && $activity->status === 'cancelled') {
            foreach ($activity->registrations()->whereIn('status', ['registered', 'waitlisted'])->with('user')->get() as $registration) {
                PortalNotification::create(['user_id' => $registration->user_id, 'title' => 'Aktiviti dibatalkan', 'message' => 'Aktiviti '.$activity->title.' telah dibatalkan.', 'type' => 'warning', 'link' => route('activities.show', $activity)]);

                if ($registration->user?->email && $registration->user->wantsEmail('activities')) {
                    $this->emailDeliveryService->send($registration->user, 'activity cancelled', new ActivityCancelledMail($activity), $activity);
                    $this->emailAuditService->sent($registration->user, 'activity cancelled', $activity);
                }
            }
        }

        return redirect()->route('activities.show', $activity)->with('status', 'Aktiviti berjaya dikemas kini.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        Gate::authorize('manage-activities');
        abort_unless($activity->created_by === auth()->id() && $activity->status === 'pending_approval', 403);
        $activity->delete();

        return redirect()->route('activities.index')->with('status', 'Aktiviti dipadam.');
    }

    public function uploadReportPhoto(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->created_by === $request->user()->id || $request->user()->hasRole('treasurer', 'admin'), 403);
        abort_unless($activity->status === 'approved' && $activity->isFinished(), 422, 'Gambar report hanya boleh dimuat naik selepas aktiviti tamat.');

        $data = $request->validate(['report_photo' => ['required', 'image', 'max:8192']], [
            'report_photo.required' => 'Sila pilih gambar report.',
            'report_photo.image' => 'Fail report mesti dalam format gambar.',
            'report_photo.max' => 'Gambar report tidak boleh melebihi 8MB.',
        ]);

        if ($activity->report_photo_path) {
            Storage::disk('private')->delete($activity->report_photo_path);
        }

        $activity->update(['report_photo_path' => $data['report_photo']->store('activity-reports', 'private')]);

        return back()->with('status', 'Gambar report berjaya dimuat naik.');
    }

    public function verify(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->status === 'pending_approval', 422, 'Hanya aktiviti yang menunggu kelulusan boleh diluluskan.');
        $data = $request->validate(['treasurer_notes' => ['nullable', 'string', 'max:1000']]);
        $activity->update(['status' => 'treasurer_verified', 'treasurer_verified_by' => $request->user()->id, 'treasurer_verified_at' => now(), 'treasurer_notes' => $data['treasurer_notes'] ?? null]);
        if ($activity->created_by) {
            PortalNotification::create(['user_id' => $activity->created_by, 'title' => 'Aktiviti disahkan bendahari', 'message' => 'Permohonan '.$activity->title.' telah disahkan bendahari dan menunggu kelulusan admin.', 'type' => 'info', 'link' => route('activities.show', $activity)]);
        }
        User::where('role', 'admin')->where('membership_status', 'active')->get()->each(fn (User $user) => PortalNotification::create(['user_id' => $user->id, 'title' => 'Aktiviti menunggu kelulusan', 'message' => 'Aktiviti '.$activity->title.' telah disahkan bendahari dan memerlukan kelulusan anda.', 'type' => 'info', 'link' => route('activities.show', $activity)]));
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'verified', 'module' => 'Activity Approval', 'record_type' => Activity::class, 'record_id' => $activity->id, 'description' => 'Treasurer verified activity '.$activity->title.'.', 'changes' => ['status' => 'treasurer_verified'], 'ip_address' => $request->ip()]);
        return back()->with('status', 'Aktiviti disahkan bendahari dan dihantar kepada admin.');
    }

    public function approve(Activity $activity): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($activity->status === 'treasurer_verified', 422, 'Aktiviti perlu disahkan bendahari dahulu.');

        $activity->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        if ($activity->created_by) {
            PortalNotification::create(['user_id' => $activity->created_by, 'title' => 'Aktiviti diluluskan', 'message' => 'Permohonan '.$activity->title.' telah diluluskan oleh admin.', 'type' => 'success', 'link' => route('activities.show', $activity)]);
        }

        User::where('membership_status', 'active')->where('role', 'member')->chunkById(100, function ($users) use ($activity): void {
            foreach ($users as $user) {
                PortalNotification::create(['user_id' => $user->id, 'title' => 'Aktiviti diluluskan', 'message' => 'Aktiviti '.$activity->title.' kini dibuka untuk pendaftaran.', 'type' => 'info', 'link' => route('activities.show', $activity)]);

                if ($user->email && $user->wantsEmail('activities')) {
                    $this->emailDeliveryService->send($user, 'activity approved', new ActivityApprovedMail($activity), $activity);
                    $this->emailAuditService->sent($user, 'activity approved', $activity);
                }
            }
        });

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'approved',
            'module' => 'Activity Approval',
            'record_type' => Activity::class,
            'record_id' => $activity->id,
            'description' => 'Approved activity '.$activity->title.'.',
            'changes' => ['status' => 'approved'],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('status', 'Aktiviti diluluskan.');
    }

    public function reject(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($activity->status === 'treasurer_verified', 422);
        $data = $request->validate(['review_notes' => ['required', 'string', 'max:1000']]);
        $activity->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_notes' => $data['review_notes']]);
        if ($activity->created_by) {
            PortalNotification::create(['user_id' => $activity->created_by, 'title' => 'Aktiviti ditolak', 'message' => 'Permohonan '.$activity->title.' telah ditolak: '.$data['review_notes'], 'type' => 'warning', 'link' => route('activities.show', $activity)]);
        }
        return back()->with('status', 'Aktiviti ditolak.');
    }

    public function refreshQrToken(Activity $activity): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('treasurer', 'admin'), 403);
        abort_if($activity->status === 'cancelled', 422, 'QR tidak boleh dijana untuk aktiviti yang telah dibatalkan.');

        $oldToken = $activity->qr_code_token;

        $activity->update([
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'module' => 'Activity QR',
            'record_type' => Activity::class,
            'record_id' => $activity->id,
            'description' => 'Refreshed attendance QR token for '.$activity->title.'.',
            'changes' => [
                'old_token' => $oldToken,
                'new_token' => $activity->qr_code_token,
            ],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('status', 'QR kehadiran baharu berjaya dijana. QR lama tidak lagi sah.');
    }

    public function attendanceStatus(Activity $activity): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->hasRole('admin', 'treasurer'), 403);

        $activity->load(['activeRegistrations.user', 'attendances']);
        $attendanceByUser = $activity->attendances->keyBy('user_id');

        return response()->json([
            'registered_count' => $activity->activeRegistrations->count(),
            'attended_count' => $activity->attendances->whereIn('user_id', $activity->activeRegistrations->pluck('user_id'))->count(),
            'participants' => $activity->activeRegistrations->map(fn ($registration) => [
                'user_id' => $registration->user_id,
                'name' => $registration->user->name,
                'attended' => $attendanceByUser->has($registration->user_id),
                'scanned_at' => $attendanceByUser->get($registration->user_id)?->scanned_at?->format('d/m/Y h:i A'),
            ])->values(),
        ]);
    }

    private function timelineLogs(Activity $activity)
    {
        if (! auth()->check() || ! auth()->user()->hasRole('admin')) {
            return collect();
        }

        return AuditLog::with('user')
            ->where('record_type', Activity::class)
            ->where('record_id', $activity->id)
            ->oldest()
            ->get();
    }
}
