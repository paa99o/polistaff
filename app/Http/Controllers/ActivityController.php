<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $activities = Activity::query()
            ->when($request->filled('date'), fn ($query) => $query->whereDate('date_time', $request->date))
            ->orderBy('date_time')
            ->paginate(10);

        return view('activities.index', compact('activities'));
    }

    public function create(): View
    {
        Gate::authorize('manage-activities');

        return view('activities.create');
    }

    public function store(ActivityRequest $request): RedirectResponse
    {
        Gate::authorize('manage-activities');

        $activity = Activity::create([...$request->validated(), 'qr_code_token' => Str::uuid()->toString()]);

        return redirect()->route('activities.show', $activity)->with('status', 'Aktiviti berjaya dicipta.');
    }

    public function show(Activity $activity): View
    {
        $activity->load('attendances.user', 'registrations.user', 'activeRegistrations.user', 'waitlistedRegistrations.user');

        return view('activities.show', compact('activity'));
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
        $activity->update($request->validated());

        if ($beforeStatus !== 'cancelled' && $activity->status === 'cancelled') {
            foreach ($activity->registrations()->whereIn('status', ['registered', 'waitlisted'])->with('user')->get() as $registration) {
                PortalNotification::create(['user_id' => $registration->user_id, 'title' => 'Aktiviti dibatalkan', 'message' => 'Aktiviti '.$activity->title.' telah dibatalkan.', 'type' => 'warning', 'link' => route('activities.show', $activity)]);
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

        $activity->update(['status' => 'approved']);

        User::where('membership_status', 'active')->where('role', 'member')->chunkById(100, function ($users) use ($activity): void {
            foreach ($users as $user) {
                PortalNotification::create(['user_id' => $user->id, 'title' => 'Aktiviti diluluskan', 'message' => 'Aktiviti '.$activity->title.' kini dibuka untuk pendaftaran.', 'type' => 'info', 'link' => route('activities.show', $activity)]);
            }
        });

        \App\Models\AuditLog::create([
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
}
