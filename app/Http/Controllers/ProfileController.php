<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $attendances = $request->user()->attendances()->with('activity')->latest('scanned_at')->get();
        $attendedCount = $attendances->pluck('activity_id')->unique()->count();
        $badgeDefinitions = [
            ['name' => 'Ahli Baru', 'threshold' => 1, 'icon' => 'bi-stars'],
            ['name' => 'Ahli Aktif', 'threshold' => 3, 'icon' => 'bi-lightning-charge'],
            ['name' => 'Ahli Komited', 'threshold' => 5, 'icon' => 'bi-award'],
            ['name' => 'Duta Polistaff', 'threshold' => 10, 'icon' => 'bi-trophy'],
        ];
        $earnedBadges = collect($badgeDefinitions)->filter(fn (array $badge) => $attendedCount >= $badge['threshold'])->values();
        $nextBadge = collect($badgeDefinitions)->first(fn (array $badge) => $attendedCount < $badge['threshold']);
        $badgeProgress = $nextBadge
            ? min(100, (int) round(($attendedCount / $nextBadge['threshold']) * 100))
            : 100;

        return view('profile.show', [
            'user' => $request->user(),
            'missingProfileFields' => $request->user()->missingProfileFields(),
            'attendances' => $attendances,
            'attendedCount' => $attendedCount,
            'earnedBadges' => $earnedBadges,
            'nextBadge' => $nextBadge,
            'badgeProgress' => $badgeProgress,
        ]);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $emailChanged = $user->email !== $request->validated('email');
        $before = $user->only(['name', 'ic_number', 'email', 'department', 'phone', 'address', 'profile_photo_path']);
        $data = $request->validated();
        unset($data['profile_photo']);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->update($data);
        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        AuditLog::create(['user_id' => $user->id, 'action' => 'updated', 'module' => 'Profile', 'record_type' => get_class($user), 'record_id' => $user->id, 'description' => 'Updated profile details.', 'changes' => ['before' => $before, 'after' => $user->fresh()->only(['name', 'ic_number', 'email', 'department', 'phone', 'address', 'profile_photo_path'])], 'ip_address' => $request->ip()]);

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return to_route('verification.notice')->with('status', 'Profil dikemas kini. Sila sahkan alamat emel baharu anda.');
        }

        return redirect()->route('profile.show')->with('status', 'Profil berjaya dikemas kini.');
    }
}
