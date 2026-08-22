<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\PaymentSubmission;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where(function ($query) use ($request): void {
                    $query->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('email', 'like', '%'.$request->search.'%')
                        ->orWhere('department', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->role))
            ->when($request->filled('status'), fn ($query) => $query->where('membership_status', $request->status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.index', [
            'users' => $users,
            'totalUsers' => User::count(),
            'pendingUsers' => User::where('membership_status', 'pending')->count(),
            'activeUsers' => User::where('membership_status', 'active')->count(),
            'totalActivities' => Activity::count(),
            'totalAttendances' => Attendance::count(),
            'pendingPayments' => PaymentSubmission::where('status', 'pending')->count(),
            'recentAuditLogs' => AuditLog::with('user')->latest()->limit(6)->get(),
            'netBalance' => Transaction::where('type', 'income')->sum('amount') - Transaction::where('type', 'expense')->sum('amount'),
        ]);
    }

    public function audit(Request $request): View
    {
        return view('admin.audit', [
            'logs' => AuditLog::with('user')
                ->when($request->filled('module'), fn ($query) => $query->where('module', 'like', '%'.$request->module.'%'))
                ->when($request->filled('action'), fn ($query) => $query->where('action', $request->action))
                ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->date))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:member,treasurer,chairman,admin'],
            'membership_status' => ['required', 'in:pending,active,inactive'],
            'fee_balance' => ['required', 'numeric', 'min:0'],
        ]);

        $data['joined_date'] = $data['membership_status'] === 'active' && ! $user->joined_date
            ? now()->toDateString()
            : $user->joined_date;

        $before = $user->only(['role', 'membership_status', 'fee_balance']);
        $user->update($data);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'module' => 'Admin User Management',
            'record_type' => User::class,
            'record_id' => $user->id,
            'description' => 'Updated role, status, or fee balance for '.$user->name.'.',
            'changes' => ['before' => $before, 'after' => $user->only(['role', 'membership_status', 'fee_balance'])],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Maklumat ahli berjaya dikemas kini.');
    }
}
