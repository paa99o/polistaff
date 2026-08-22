<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\AuditLog;
use App\Models\PortalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityRegistrationController extends Controller
{
    public function store(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->registrationIsOpen(), 422, 'Pendaftaran aktiviti belum dibuka atau sudah ditutup.');

        $status = $activity->hasCapacity() ? 'registered' : 'waitlisted';

        $registration = ActivityRegistration::updateOrCreate([
            'user_id' => $request->user()->id,
            'activity_id' => $activity->id,
        ], [
            'status' => $status,
            'registered_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'registered',
            'module' => 'Activity Registration',
            'record_type' => ActivityRegistration::class,
            'record_id' => $registration->id,
            'description' => 'Registered for activity '.$activity->title.'.',
            'changes' => ['activity' => $activity->title, 'status' => $status],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', $status === 'registered' ? 'Pendaftaran aktiviti berjaya. Anda kini boleh rekod kehadiran semasa aktiviti.' : 'Kapasiti penuh. Anda dimasukkan ke waiting list.');
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse
    {
        $registration = ActivityRegistration::where('user_id', $request->user()->id)
            ->where('activity_id', $activity->id)
            ->firstOrFail();

        $registration->update(['status' => 'cancelled']);

        $promoted = null;
        if ($activity->hasCapacity()) {
            $promoted = ActivityRegistration::where('activity_id', $activity->id)->where('status', 'waitlisted')->oldest('registered_at')->first();
            if ($promoted) {
                $promoted->update(['status' => 'registered']);
                PortalNotification::create(['user_id' => $promoted->user_id, 'title' => 'Waiting list diluluskan', 'message' => 'Anda telah dipromosikan sebagai peserta aktiviti '.$activity->title.'.', 'type' => 'success', 'link' => route('activities.show', $activity)]);
            }
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'cancelled',
            'module' => 'Activity Registration',
            'record_type' => ActivityRegistration::class,
            'record_id' => $registration->id,
            'description' => 'Cancelled registration for activity '.$activity->title.'.',
            'changes' => ['activity' => $activity->title, 'status' => 'cancelled', 'promoted_registration_id' => $promoted?->id],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Pendaftaran aktiviti dibatalkan.');
    }
}
