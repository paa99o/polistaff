<?php

namespace App\Http\Controllers;

use App\Mail\MembershipApprovedMail;
use App\Mail\MembershipRejectedMail;
use App\Models\AuditLog;
use App\Models\PortalNotification;
use App\Models\User;
use App\Services\EmailAuditService;
use App\Services\EmailDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminMemberController extends Controller
{
    public function __construct(private EmailAuditService $emailAuditService, private EmailDeliveryService $emailDeliveryService) {}

    public function pending(): View
    {
        return view('admin.members.pending', ['members' => User::where('membership_status', 'pending')->latest()->paginate(15)]);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->membership_status === 'pending', 422, 'Permohonan ini sudah disemak.');

        $user->update([
            'membership_status' => 'active',
            'membership_review_notes' => null,
            'joined_date' => now()->toDateString(),
        ]);

        PortalNotification::create([
            'user_id' => $user->id,
            'title' => 'Keahlian diluluskan',
            'message' => 'Permohonan keahlian anda telah diluluskan.',
            'type' => 'success',
            'link' => route('dashboard'),
        ]);

        if ($user->email) {
            $this->emailDeliveryService->send($user, 'membership approved', new MembershipApprovedMail($user), $user);
            $this->emailAuditService->sent($user, 'membership approved', $user);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'approved',
            'module' => 'Membership Approval',
            'record_type' => User::class,
            'record_id' => $user->id,
            'description' => 'Approved membership application for '.$user->name.'.',
            'changes' => ['membership_status' => 'active'],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('status', 'Ahli berjaya diluluskan dan emel dihantar.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->membership_status === 'pending', 422, 'Permohonan ini sudah disemak.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Sila isi sebab permohonan ditolak.',
        ]);

        $user->update([
            'membership_status' => 'inactive',
            'membership_review_notes' => $data['reason'],
        ]);

        PortalNotification::create([
            'user_id' => $user->id,
            'title' => 'Permohonan ahli ditolak',
            'message' => 'Permohonan keahlian anda ditolak: '.$data['reason'],
            'type' => 'warning',
            'link' => route('membership.apply'),
        ]);

        if ($user->email) {
            $this->emailDeliveryService->send($user, 'membership rejected', new MembershipRejectedMail($user, $data['reason']), $user);
            $this->emailAuditService->sent($user, 'membership rejected', $user);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'rejected',
            'module' => 'Membership Approval',
            'record_type' => User::class,
            'record_id' => $user->id,
            'description' => 'Rejected membership application for '.$user->name.'.',
            'changes' => ['membership_status' => 'inactive', 'reason' => $data['reason']],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Permohonan ahli ditolak dan emel dihantar.');
    }
}
