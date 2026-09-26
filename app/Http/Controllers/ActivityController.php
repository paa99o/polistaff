<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityRequest;
use App\Mail\ActivityApprovedMail;
use App\Mail\ActivityCancelledMail;
use App\Models\Activity;
use App\Models\ActivityEvidencePhoto;
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
            ->where('status', '!=', 'draft')
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

        $scope = $user->hasRole('admin', 'treasurer') ? Activity::query() : Activity::where('created_by', $user->id);
        $stats = [
            'approved' => (clone $scope)->where('status', 'approved')->count(),
            'rejected' => (clone $scope)->where('status', 'rejected')->count(),
            'pending' => (clone $scope)->where('status', 'pending_approval')->count(),
            'treasurer_verified' => (clone $scope)->where('status', 'treasurer_verified')->count(),
            'draft' => $user->hasRole('member') ? Activity::where('created_by', $user->id)->where('status', 'draft')->count() : 0,
        ];

        return view('activities.index', compact('calendarMonth', 'calendarWeeks', 'stats'));
    }

    public function statusList(Request $request, string $status): View
    {
        abort_unless(in_array($status, ['approved', 'rejected', 'pending_approval', 'treasurer_verified', 'draft'], true), 404);
        abort_unless($status !== 'draft' || $request->user()->hasRole('member'), 404);

        $user = $request->user();
        $scope = $user->hasRole('admin', 'treasurer')
            ? Activity::query()
            : Activity::where('created_by', $user->id);
        $activities = $scope->where('status', $status)
            ->orderByDesc('date_time')
            ->paginate(15);

        return view('activities.status-list', compact('activities', 'status'));
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
        $activity->load('evidencePhotos.user');

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
        $isWizard = ($data['wizard'] ?? null) === '1';
        $isDraft = $isWizard && ($data['intent'] ?? 'submit') === 'draft';
        $activityData = $isWizard ? $this->wizardActivityData($data) : $data;
        $activityData['status'] = $isDraft ? 'draft' : 'pending_approval';
        $activityData['created_by'] = $request->user()->id;
        $activityData['qr_code_token'] = null;
        $activity = Activity::create($activityData);

        if (! $isDraft) {
            $this->notifyTreasurers($activity);
        }

        return $isDraft
            ? redirect()->route('activities.index')->with('status', 'Draf aktiviti disimpan. Anda boleh sambung semula kemudian.')
            : redirect()->route('activities.show', $activity)->with('status', 'Aktiviti berjaya dihantar kepada bendahari.');
    }

    public function show(Activity $activity): View
    {
        $user = auth()->user();
        $canManageAttendance = $user?->hasRole('admin', 'treasurer') ?? false;
        $canGenerateQr = $user?->hasRole('treasurer') ?? false;

        abort_unless($activity->status === 'approved' || $activity->created_by === $user?->id || $user?->hasRole('admin', 'treasurer'), 404);

        $activity->loadCount(['attendances', 'activeRegistrations', 'waitlistedRegistrations']);
        $registration = $user ? $activity->registrations()->where('user_id', $user->id)->first() : null;

        if ($canManageAttendance) {
            $activity->load('attendances.user', 'activeRegistrations.user', 'waitlistedRegistrations.user', 'guestRegistrations', 'evidencePhotos.user');
        } else {
            $activity->load('evidencePhotos.user');
        }

        return view('activities.show', [
            'activity' => $activity,
            'registration' => $registration,
            'canManageAttendance' => $canManageAttendance,
            'canGenerateQr' => $canGenerateQr,
            'timelineLogs' => $this->timelineLogs($activity),
        ]);
    }

    public function edit(Activity $activity): View
    {
        Gate::authorize('manage-activities');
        abort_unless($activity->created_by === auth()->id() && in_array($activity->status, ['draft', 'pending_approval'], true), 403);

        return view('activities.edit', compact('activity'));
    }

    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        Gate::authorize('manage-activities');
        abort_unless($activity->created_by === auth()->id() && in_array($activity->status, ['draft', 'pending_approval'], true), 403);

        $beforeStatus = $activity->status;
        $data = $request->validated();
        $isWizard = ($data['wizard'] ?? null) === '1';
        $isDraft = $isWizard && ($data['intent'] ?? 'submit') === 'draft';
        $activityData = $isWizard ? $this->wizardActivityData($data, $activity) : $data;
        $activityData['status'] = $isDraft && $beforeStatus === 'draft' ? 'draft' : 'pending_approval';
        $activity->update($activityData);

        if ($beforeStatus === 'draft' && $activity->status === 'pending_approval') {
            $this->notifyTreasurers($activity);
        }

        if ($beforeStatus !== 'cancelled' && $activity->status === 'cancelled') {
            foreach ($activity->registrations()->whereIn('status', ['registered', 'waitlisted'])->with('user')->get() as $registration) {
                PortalNotification::create(['user_id' => $registration->user_id, 'title' => 'Aktiviti dibatalkan', 'message' => 'Aktiviti '.$activity->title.' telah dibatalkan.', 'type' => 'warning', 'link' => route('activities.show', $activity)]);

                if ($registration->user?->email && $registration->user->wantsEmail('activities')) {
                    $this->emailDeliveryService->send($registration->user, 'activity cancelled', new ActivityCancelledMail($activity), $activity);
                    $this->emailAuditService->sent($registration->user, 'activity cancelled', $activity);
                }
            }
        }

        return $activity->status === 'draft'
            ? redirect()->route('activities.index')->with('status', 'Draf aktiviti dikemas kini.')
            : redirect()->route('activities.show', $activity)->with('status', $beforeStatus === 'draft' ? 'Aktiviti berjaya dihantar kepada bendahari.' : 'Aktiviti berjaya dikemas kini.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        Gate::authorize('manage-activities');
        abort_unless($activity->created_by === auth()->id() && in_array($activity->status, ['draft', 'pending_approval'], true), 403);
        $activity->delete();

        return redirect()->route('activities.index')->with('status', 'Aktiviti dipadam.');
    }

    public function uploadEvidencePhotos(Request $request, Activity $activity): RedirectResponse
    {
        $isRegistered = $activity->registrations()->where('user_id', $request->user()->id)->where('status', 'registered')->exists();
        abort_unless($isRegistered || $request->user()->hasRole('treasurer', 'admin'), 403);
        abort_unless($activity->status === 'approved' && $activity->isFinished(), 422, 'Gambar bukti hanya boleh dimuat naik selepas aktiviti tamat.');

        $data = $request->validate(['photos' => ['required', 'array', 'min:1', 'max:10'], 'photos.*' => ['required', 'image', 'max:8192']], [
            'photos.required' => 'Sila pilih sekurang-kurangnya satu gambar.',
            'photos.*.image' => 'Semua fail mesti dalam format gambar.',
            'photos.*.max' => 'Setiap gambar tidak boleh melebihi 8MB.',
        ]);

        foreach ($data['photos'] as $photo) {
            ActivityEvidencePhoto::create([
                'activity_id' => $activity->id,
                'user_id' => $request->user()->id,
                'path' => $photo->store('activity-evidence', 'public'),
            ]);
        }

        return back()->with('status', 'Gambar bukti aktiviti berjaya dimuat naik.');
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
        $isTreasurerRequest = $request->user()->hasRole('treasurer');
        abort_unless($isTreasurerRequest || $request->user()->hasRole('admin'), 403);
        abort_unless(
            $isTreasurerRequest ? $activity->status === 'pending_approval' : $activity->status === 'treasurer_verified',
            422
        );
        $data = $request->validate(['review_notes' => ['required', 'string', 'max:1000']]);
        $activity->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_notes' => $data['review_notes']]);
        if ($activity->created_by) {
            PortalNotification::create(['user_id' => $activity->created_by, 'title' => 'Aktiviti ditolak', 'message' => 'Permohonan '.$activity->title.' telah ditolak: '.$data['review_notes'], 'type' => 'warning', 'link' => route('activities.show', $activity)]);
        }
        return back()->with('status', 'Aktiviti ditolak.');
    }

    public function refreshQrToken(Activity $activity): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('treasurer'), 403);
        abort_if($activity->status === 'cancelled', 422, 'QR tidak boleh dijana untuk aktiviti yang telah dibatalkan.');
        abort_unless($activity->status === 'approved' && now()->between($activity->date_time, $activity->end_time ?? $activity->date_time), 422, 'QR hanya boleh dijana semasa aktiviti berlangsung.');

        $oldToken = $activity->qr_code_token;

        $activity->update([
            'qr_code_token' => Str::uuid()->toString(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $oldToken ? 'updated' : 'generated',
            'module' => 'Activity QR',
            'record_type' => Activity::class,
            'record_id' => $activity->id,
            'description' => ($oldToken ? 'Regenerated' : 'Generated').' attendance QR token for '.$activity->title.'.',
            'changes' => [
                'old_token' => $oldToken,
                'new_token' => $activity->qr_code_token,
            ],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('status', $oldToken
            ? 'QR kehadiran berjaya dijana semula. QR lama tidak lagi sah.'
            : 'QR kehadiran berjaya dijana.');
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

    private function wizardActivityData(array $data, ?Activity $activity = null): array
    {
        $startAt = filled($data['start_date'] ?? null) && filled($data['start_time'] ?? null)
            ? Carbon::parse($data['start_date'].' '.$data['start_time'])
            : ($activity?->date_time ?? now());
        $endAt = filled($data['end_date'] ?? null) && filled($data['end_time'] ?? null)
            ? Carbon::parse($data['end_date'].' '.$data['end_time'])
            : ($activity?->end_time ?? $startAt->copy()->addHour());

        $proposalData = [];
        foreach (['objectives', 'target_participants', 'tentative', 'committee', 'budget_items', 'funding_sources'] as $key) {
            $values = $data[$key] ?? [];
            $proposalData[$key] = collect($values)->filter(function ($value): bool {
                if (is_array($value)) {
                    return collect($value)->contains(fn ($item) => filled($item));
                }

                return filled($value);
            })->values()->all();
        }

        return [
            'title' => filled($data['title'] ?? null) ? $data['title'] : ($activity?->title ?? 'Draf aktiviti'),
            'activity_type' => $data['activity_type'] ?? $activity?->activity_type,
            'program_category' => $data['program_category'] ?? $activity?->program_category,
            'organizing_unit' => $data['organizing_unit'] ?? $activity?->organizing_unit,
            'person_in_charge' => $data['person_in_charge'] ?? $activity?->person_in_charge,
            'description' => null,
            'date_time' => $startAt,
            'end_time' => $endAt,
            'location' => filled($data['location'] ?? null) ? $data['location'] : ($activity?->location ?? 'Belum ditetapkan'),
            'max_participants' => $data['expected_participants'] ?? $activity?->max_participants,
            'expected_participants' => $data['expected_participants'] ?? $activity?->expected_participants,
            'participant_criteria' => $data['participant_criteria'] ?? $activity?->participant_criteria,
            'implementation_mode' => $data['implementation_mode'] ?? $activity?->implementation_mode,
            'proposal_data' => $proposalData,
            'registration_opens_at' => filled($data['registration_opens_at'] ?? null) ? $data['registration_opens_at'] : null,
            'registration_closes_at' => filled($data['registration_closes_at'] ?? null) ? $data['registration_closes_at'] : null,
        ];
    }

    private function notifyTreasurers(Activity $activity): void
    {
        User::where('role', 'treasurer')->where('membership_status', 'active')->get()->each(function (User $user) use ($activity): void {
            PortalNotification::create([
                'user_id' => $user->id,
                'title' => 'Permohonan aktiviti baharu',
                'message' => $activity->title.' menunggu kelulusan bendahari.',
                'type' => 'info',
                'link' => route('activities.show', $activity),
            ]);
        });
    }
}
