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
        $month = $request->input('month', now()->format('Y-m'));
        $calendarMonth = preg_match('/^\d{4}-\d{2}$/', $month)
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();
        $calendarStart = $calendarMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $calendarActivities = Activity::query()
            ->when(! $request->user()->hasRole('admin', 'chairman'), fn ($query) => $query->where('status', 'approved'))
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
            ->when(! $request->user()->hasRole('admin', 'chairman'), fn ($query) => $query->where('status', 'approved'))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('date_time', $request->date))
            ->orderBy('date_time')
            ->paginate(10);

        return view('activities.index', compact('activities', 'calendarMonth', 'calendarWeeks'));
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

        $activity = Activity::create([...$data, 'qr_code_token' => Str::uuid()->toString()]);

        return redirect()->route('activities.show', $activity)->with('status', 'Aktiviti berjaya dicipta.');
    }

    public function show(Activity $activity): View
    {
        $user = auth()->user();
        $canManageAttendance = $user->hasRole('admin', 'chairman', 'treasurer');

        abort_unless($activity->status === 'approved' || $user->hasRole('admin', 'chairman'), 404);

        $activity->loadCount(['attendances', 'activeRegistrations', 'waitlistedRegistrations']);
        $registration = $activity->registrations()->where('user_id', $user->id)->first();

        if ($canManageAttendance) {
            $activity->load('attendances.user', 'activeRegistrations.user', 'waitlistedRegistrations.user');
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

        return view('activities.edit', compact('activity'));
    }

    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        Gate::authorize('manage-activities');

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
        $activity->delete();

        return redirect()->route('activities.index')->with('status', 'Aktiviti dipadam.');
    }

    public function approve(Activity $activity): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('chairman', 'admin'), 403);
        abort_unless($activity->status === 'pending_approval', 422, 'Hanya aktiviti yang menunggu kelulusan boleh diluluskan.');

        $activity->update(['status' => 'approved']);

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

        return back()->with('status', 'Aktiviti diluluskan. Emel diproses mengikut tetapan ahli aktif.');
    }

    public function refreshQrToken(Activity $activity): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('chairman', 'admin', 'treasurer'), 403);
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

    private function timelineLogs(Activity $activity)
    {
        if (! auth()->user()->hasRole('chairman', 'admin')) {
            return collect();
        }

        return AuditLog::with('user')
            ->where('record_type', Activity::class)
            ->where('record_id', $activity->id)
            ->oldest()
            ->get();
    }
}
