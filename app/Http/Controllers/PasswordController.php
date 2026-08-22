<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('profile.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(8)]]);
        $request->user()->update(['password' => Hash::make($data['password'])]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'changed-password', 'module' => 'Profile', 'record_type' => get_class($request->user()), 'record_id' => $request->user()->id, 'description' => 'Changed account password.', 'changes' => [], 'ip_address' => $request->ip()]);

        return back()->with('status', 'Kata laluan dikemas kini.');
    }
}
