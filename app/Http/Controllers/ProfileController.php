<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $before = $request->user()->only(['name', 'department', 'phone']);
        $request->user()->update($request->validated());
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'updated', 'module' => 'Profile', 'record_type' => get_class($request->user()), 'record_id' => $request->user()->id, 'description' => 'Updated profile details.', 'changes' => ['before' => $before, 'after' => $request->user()->only(['name', 'department', 'phone'])], 'ip_address' => $request->ip()]);

        return redirect()->route('profile.show')->with('status', 'Profil berjaya dikemas kini.');
    }
}
