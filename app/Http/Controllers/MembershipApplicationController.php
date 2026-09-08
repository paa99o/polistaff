<?php

namespace App\Http\Controllers;

use App\Http\Requests\MembershipApplicationRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipApplicationController extends Controller
{
    public function create(Request $request): View
    {
        abort_if($request->user()->membership_status === 'active', 403);

        return view('membership.apply', ['user' => $request->user()]);
    }

    public function store(MembershipApplicationRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->membership_status === 'active', 403);

        $before = $user->only(['name', 'ic_number', 'department', 'phone', 'address', 'membership_status']);

        $user->update([
            ...$request->validated(),
            'membership_status' => 'pending',
            'membership_review_notes' => null,
            'fee_balance' => max((float) $user->fee_balance, 10),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'submitted',
            'module' => 'Membership',
            'record_type' => User::class,
            'record_id' => $user->id,
            'description' => 'Submitted club staff membership application.',
            'changes' => ['before' => $before, 'after' => $user->fresh()->only(['name', 'ic_number', 'department', 'phone', 'address', 'membership_status'])],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('dashboard')->with('status', 'Permohonan ahli kelab staf berjaya dihantar dan menunggu semakan admin.');
    }
}
