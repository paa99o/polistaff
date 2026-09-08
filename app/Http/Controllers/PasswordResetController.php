<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Sila masukkan alamat emel anda.',
            'email.email' => 'Format alamat emel tidak sah.',
        ]);

        Password::sendResetLink($data);

        return back()->with('status', 'Jika emel tersebut berdaftar, pautan reset kata laluan telah dihantar.');
    }

    public function reset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'email.required' => 'Sila masukkan alamat emel anda.',
            'email.email' => 'Format alamat emel tidak sah.',
            'password.required' => 'Sila masukkan kata laluan baharu.',
            'password.confirmed' => 'Pengesahan kata laluan tidak sepadan.',
            'password.min' => 'Kata laluan mestilah sekurang-kurangnya 8 aksara.',
        ]);

        $status = Password::reset(
            $data,
            function (User $user, string $password) use ($request): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'reset-password',
                    'module' => 'Authentication',
                    'record_type' => User::class,
                    'record_id' => $user->id,
                    'description' => 'User reset their password through email recovery.',
                    'changes' => ['email' => $user->email],
                    'ip_address' => $request->ip(),
                ]);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => $status === Password::INVALID_TOKEN
                    ? 'Pautan reset tidak sah atau telah tamat tempoh.'
                    : 'Kata laluan tidak dapat ditetapkan semula. Sila minta pautan baharu.',
            ])->withInput($request->only('email'));
        }

        return to_route('login')->with('status', 'Kata laluan berjaya ditetapkan semula. Anda boleh log masuk sekarang.');
    }
}
