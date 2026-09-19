<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\AuditLog;
use App\Mail\GuestActivityRegisteredMail;
use App\Models\GuestActivityRegistration;
use App\Models\PortalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ActivityRegistrationController extends Controller
{
    public function guestStore(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($activity->registrationIsOpen(), 422, 'Pendaftaran aktiviti belum dibuka atau sudah ditutup.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:40'],
        ]);

        $existing = GuestActivityRegistration::where('activity_id', $activity->id)
            ->where('email', $data['email'])
            ->first();

        if ($existing?->status === 'registered') {
            return back()->with('status', 'Emel ini sudah berdaftar untuk aktiviti tersebut.');
        }

        $registeredCount = $activity->activeRegistrations()->count()
            + $activity->guestRegistrations()->where('status', 'registered')->count();
        $status = $activity->max_participants !== null && $registeredCount >= $activity->max_participants
            ? 'waitlisted'
            : 'registered';

        $registration = GuestActivityRegistration::updateOrCreate(
            ['activity_id' => $activity->id, 'email' => $data['email']],
            [...$data, 'status' => $status, 'registered_at' => now()]
        );

        AuditLog::create([
            'user_id' => null,
            'action' => 'guest_registered',
            'module' => 'Activity Registration',
            'record_type' => GuestActivityRegistration::class,
            'record_id' => $registration->id,
            'description' => 'Guest registered for activity '.$activity->title.'.',
            'changes' => ['activity' => $activity->title, 'email' => $registration->email, 'status' => $status],
            'ip_address' => $request->ip(),
        ]);

        try {
            Mail::to($registration->email)->send(new GuestActivityRegisteredMail($registration->load('activity')));
        } catch (\Throwable) {
            // Simpan pendaftaran walaupun pelayan emel belum dikonfigurasi semasa ujian.
        }

        return back()->with('status', $status === 'registered'
            ? 'Pendaftaran berjaya. Pengesahan telah dihantar ke emel anda.'
            : 'Kapasiti penuh. Anda dimasukkan ke senarai menunggu dan akan dimaklumkan melalui emel.');
    }

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
