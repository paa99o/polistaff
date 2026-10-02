<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        $alreadyVerified = $user->hasVerifiedEmail();
        if (! $alreadyVerified) {
            $user->markEmailAsVerified();
        }

        if (! $alreadyVerified) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'verified-email',
                'module' => 'Authentication',
                'record_type' => User::class,
                'record_id' => $user->id,
                'description' => 'User verified their email address.',
                'changes' => ['email' => $user->email],
                'ip_address' => $request->ip(),
            ]);
        }

        $currentUser = $request->user();
        $destination = $currentUser?->hasRole('admin')
            ? route('admin.members.pending')
            : ($currentUser?->is($user) ? route('dashboard') : route('login'));

        return redirect($destination)->with('status', 'Alamat emel '.$user->email.' berjaya disahkan. Admin boleh refresh senarai kelulusan ahli.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('status', 'Emel pengesahan gagal dihantar. Sila cuba semula sebentar lagi atau hubungi admin.')
                ->with('email_error', true);
        }

        return back()->with('status', 'Pautan pengesahan baharu telah dihantar.')
            ->with('email_error', false);
    }
}
