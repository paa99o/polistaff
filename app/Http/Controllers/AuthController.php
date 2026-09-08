<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['profile_photo']);

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'role' => 'member',
            'membership_status' => 'inactive',
        ]);

        Auth::login($user);
        $user->sendEmailVerificationNotification();
        AuditLog::create(['user_id' => $user->id, 'action' => 'registered', 'module' => 'Authentication', 'record_type' => User::class, 'record_id' => $user->id, 'description' => 'New account registered and logged in.', 'changes' => ['email' => $user->email, 'membership_status' => $user->membership_status], 'ip_address' => $request->ip()]);

        return redirect()->route('verification.notice')->with('status', 'Pendaftaran berjaya. Sila sahkan alamat emel anda.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['email' => 'Terlalu banyak cubaan log masuk. Cuba semula dalam '.RateLimiter::availableIn($throttleKey).' saat.'])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);
            AuditLog::create(['user_id' => null, 'action' => 'failed-login', 'module' => 'Authentication', 'description' => 'Failed login attempt for '.$credentials['email'].'.', 'changes' => ['email' => $credentials['email']], 'ip_address' => $request->ip()]);

            return back()->withErrors(['email' => 'Maklumat log masuk tidak sah.'])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'login', 'module' => 'Authentication', 'record_type' => User::class, 'record_id' => $request->user()->id, 'description' => 'User logged in.', 'changes' => ['email' => $request->user()->email], 'ip_address' => $request->ip()]);

        if (! $request->user()->hasVerifiedEmail()) {
            return to_route('verification.notice');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        AuditLog::create(['user_id' => $user?->id, 'action' => 'logout', 'module' => 'Authentication', 'record_type' => User::class, 'record_id' => $user?->id, 'description' => 'User logged out.', 'changes' => ['email' => $user?->email], 'ip_address' => $request->ip()]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
