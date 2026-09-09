<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\ExpenseClaim;
use App\Models\PaymentSubmission;
use App\Models\PolimartReport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
            ->when($request->input('profile') === 'complete', fn ($query) => $query->profileComplete())
            ->when($request->input('profile') === 'incomplete', fn ($query) => $query->profileIncomplete())
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
            'pendingClaims' => ExpenseClaim::whereIn('status', ['pending', 'treasurer_verified'])->count(),
            'pendingActivities' => Activity::where('status', 'pending_approval')->count(),
            'incompleteProfiles' => User::profileIncomplete()->count(),
            'outstandingFees' => User::where('membership_status', 'active')->sum('fee_balance'),
            'upcomingActivities' => Activity::where('status', 'approved')->where('date_time', '>=', now())->orderBy('date_time')->limit(4)->get(),
            'recentAuditLogs' => AuditLog::with('user')->latest()->limit(6)->get(),
            'netBalance' => Transaction::where('status', 'active')->where('type', 'income')->sum('amount')
                - Transaction::where('status', 'active')->where('type', 'expense')->sum('amount'),
            'queuedJobs' => DB::table('jobs')->count(),
            'failedJobs' => DB::table('failed_jobs')->count(),
            'pendingPolimartReports' => PolimartReport::where('status', 'pending')->count(),
        ]);
    }

    public function retryFailedJobs(Request $request): RedirectResponse
    {
        $failedJobs = DB::table('failed_jobs')->count();

        if ($failedJobs > 0) {
            Artisan::call('queue:retry', ['id' => ['all']]);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'retried',
            'module' => 'Email Queue',
            'description' => 'Retried failed queued jobs.',
            'changes' => ['failed_jobs' => $failedJobs],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', $failedJobs > 0
            ? $failedJobs.' job gagal dimasukkan semula ke dalam queue.'
            : 'Tiada job gagal untuk dicuba semula.');
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

        abort_if(
            $request->user()->is($user) && ($data['role'] !== 'admin' || $data['membership_status'] !== 'active'),
            422,
            'Anda tidak boleh membuang akses admin atau menyahaktifkan akaun sendiri.'
        );

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
