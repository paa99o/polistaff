<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Feedback;
use App\Models\PaymentSubmission;
use App\Models\PortalNotification;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.index', [
            'upcomingActivities' => Activity::where('date_time', '>=', now())->where('status', 'approved')->orderBy('date_time')->limit(5)->get(),
            'notifications' => PortalNotification::where(fn ($query) => $query->where('user_id', $user->id)->orWhereNull('user_id'))->latest()->limit(5)->get(),
            'latestPayments' => PaymentSubmission::where('user_id', $user->id)->latest()->limit(3)->get(),
            'pendingMembers' => User::where('membership_status', 'pending')->count(),
            'activeMembers' => User::where('membership_status', 'active')->count(),
            'pendingPayments' => PaymentSubmission::where('status', 'pending')->count(),
            'approvedPayments' => PaymentSubmission::where('status', 'approved')->count(),
            'totalActivities' => Activity::count(),
            'approvedActivities' => Activity::where('status', 'approved')->count(),
            'totalAttendances' => Attendance::count(),
            'totalFeedbacks' => Feedback::count(),
            'recentTransactions' => Transaction::with('user')->where('status', 'active')->latest('transaction_date')->limit(5)->get(),
            'recentAuditLogs' => AuditLog::with('user')->latest()->limit(5)->get(),
            'income' => Transaction::where('status', 'active')->where('type', 'income')->sum('amount'),
            'expenses' => Transaction::where('status', 'active')->where('type', 'expense')->sum('amount'),
            'monthlyFinance' => collect(range(5, 0))->map(function (int $monthsAgo): array {
                $month = now()->subMonths($monthsAgo);
                $income = (float) Transaction::where('status', 'active')->where('type', 'income')->whereYear('transaction_date', $month->year)->whereMonth('transaction_date', $month->month)->sum('amount');
                $expenses = (float) Transaction::where('status', 'active')->where('type', 'expense')->whereYear('transaction_date', $month->year)->whereMonth('transaction_date', $month->month)->sum('amount');

                return ['label' => $month->format('M'), 'income' => $income, 'expenses' => $expenses];
            }),
        ]);
    }
}
