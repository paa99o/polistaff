<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\Attendance;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $attendances = Attendance::with('user', 'activity')
            ->when($request->filled('member'), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$request->member.'%')))
            ->when($request->filled('activity'), fn ($query) => $query->whereHas('activity', fn ($activityQuery) => $activityQuery->where('title', 'like', '%'.$request->activity.'%')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('scanned_at', $request->date))
            ->latest('scanned_at')
            ->paginate(20)
            ->withQueryString();

        return view('attendance.index', ['attendances' => $attendances]);
    }

    public function scan(): View
    {
        return view('attendance.scan');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'string']]);
        $activity = Activity::where('qr_code_token', $data['token'])->firstOrFail();

        if (! $activity->attendanceIsOpen()) {
            return redirect()->route('activities.show', $activity)->withErrors(['token' => 'Kehadiran belum dibuka atau sudah ditutup untuk aktiviti ini.']);
        }

        $isRegistered = ActivityRegistration::where('user_id', $request->user()->id)
            ->where('activity_id', $activity->id)
            ->where('status', 'registered')
            ->exists();

        if (! $isRegistered && ! $request->user()->hasRole('admin', 'chairman', 'treasurer')) {
            return redirect()->route('activities.show', $activity)->withErrors(['token' => 'Sila daftar aktiviti dahulu sebelum rekod kehadiran.']);
        }

        $attendance = Attendance::firstOrCreate([
            'user_id' => $request->user()->id,
            'activity_id' => $activity->id,
        ], [
            'scanned_at' => now(),
            'qr_code_token' => $activity->qr_code_token,
        ]);

        if ($attendance->wasRecentlyCreated) {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'created',
                'module' => 'QR Attendance',
                'record_type' => Attendance::class,
                'record_id' => $attendance->id,
                'description' => 'Attendance recorded automatically for '.$activity->title.'.',
                'changes' => ['activity' => $activity->title, 'scanned_at' => $attendance->scanned_at],
                'ip_address' => $request->ip(),
            ]);
        }

        return redirect()->route('activities.show', $activity)->with('status', 'Kehadiran berjaya direkodkan.');
    }
}
