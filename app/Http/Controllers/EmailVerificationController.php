<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $alreadyVerified = $request->user()->hasVerifiedEmail();
        $request->fulfill();

        if (! $alreadyVerified) {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'verified-email',
                'module' => 'Authentication',
                'record_type' => User::class,
                'record_id' => $request->user()->id,
                'description' => 'User verified their email address.',
                'changes' => ['email' => $request->user()->email],
                'ip_address' => $request->ip(),
            ]);
        }

        return to_route('dashboard')->with('status', 'Alamat emel berjaya disahkan.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Pautan pengesahan baharu telah dihantar.');
    }
}
