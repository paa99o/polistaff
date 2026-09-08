<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function overview(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin', 'chairman', 'treasurer'), 403);

        $year = (int) $request->input('year', now()->year);
        $month = $request->filled('month') ? (int) $request->month : now()->month;

        $transactions = Transaction::with('user')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->where('status', 'active')
            ->orderByDesc('transaction_date')
            ->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');
        $activities = Activity::withCount(['activeRegistrations', 'attendances'])
            ->whereYear('date_time', $year)
            ->whereMonth('date_time', $month)
            ->orderByDesc('date_time')
            ->get();

        $registeredTotal = $activities->sum('active_registrations_count');
        $attendanceTotal = $activities->sum('attendances_count');
        $attendanceRate = $registeredTotal > 0 ? round(($attendanceTotal / $registeredTotal) * 100) : 0;

        $monthlyTrend = collect(range(5, 0))->map(function (int $monthsBack) use ($year, $month) {
            $date = now()->setDate($year, $month, 1)->subMonths($monthsBack);
            $rows = Transaction::where('status', 'active')
                ->whereYear('transaction_date', $date->year)
                ->whereMonth('transaction_date', $date->month)
                ->get();

            return [
                'label' => $date->format('M'),
                'income' => (float) $rows->where('type', 'income')->sum('amount'),
                'expenses' => (float) $rows->where('type', 'expense')->sum('amount'),
            ];
        });

        $highestTrendValue = max(1, $monthlyTrend->max(fn (array $item) => max($item['income'], $item['expenses'])));

        $departmentBreakdown = User::query()
            ->selectRaw('COALESCE(department, ?) as department, COUNT(*) as total', ['Tidak dinyatakan'])
            ->where('membership_status', 'active')
            ->groupBy('department')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return view('reports.overview', [
            'year' => $year,
            'month' => $month,
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $income - $expenses,
            'activeMembers' => User::where('membership_status', 'active')->count(),
            'outstandingFees' => User::where('membership_status', 'active')->sum('fee_balance'),
            'activities' => $activities,
            'activityCount' => $activities->count(),
            'attendanceTotal' => $attendanceTotal,
            'registeredTotal' => $registeredTotal,
            'attendanceRate' => $attendanceRate,
            'recentTransactions' => $transactions->take(8),
            'monthlyTrend' => $monthlyTrend,
            'highestTrendValue' => $highestTrendValue,
            'departmentBreakdown' => $departmentBreakdown,
        ]);
    }

    public function financial(Request $request): View
    {
        Gate::authorize('view-financial-reports');

        return view('reports.financial', $this->financialReportData($request));
    }

    public function financialPdf(Request $request): Response
    {
        Gate::authorize('view-financial-reports');

        $data = $this->financialReportData($request);

        $pdf = new Dompdf;
        $pdf->loadHtml(view('reports.financial_pdf', $data)->render());
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="laporan-kewangan.pdf"',
        ]);
    }

    public function financialCsv(Request $request): Response
    {
        Gate::authorize('view-financial-reports');

        $data = $this->financialReportData($request);
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['date', 'type', 'category', 'description', 'amount']);

        foreach ($data['transactions'] as $row) {
            fputcsv($handle, [
                $row->transaction_date->format('Y-m-d'),
                $row->type,
                $row->category,
                $row->description,
                $row->amount,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="financial-report.csv"']);
    }

    public function attendanceCsv(): Response
    {
        Gate::authorize('view-financial-reports');

        $csv = "member,activity,scanned_at\n";
        foreach (Attendance::with('user', 'activity')->latest('scanned_at')->get() as $row) {
            $csv .= sprintf("%s,%s,%s\n", str_replace(',', ' ', $row->user->name), str_replace(',', ' ', $row->activity->title), $row->scanned_at->format('Y-m-d H:i:s'));
        }

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="attendance-report.csv"']);
    }

    public function activityAttendanceCsv(Activity $activity): Response
    {
        abort_unless(auth()->user()->hasRole('admin', 'chairman', 'treasurer'), 403);

        $rows = Attendance::with('user')
            ->where('activity_id', $activity->id)
            ->orderBy('scanned_at')
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['member', 'email', 'department', 'activity', 'scanned_at']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->user->name,
                $row->user->email,
                $row->user->department ?? '-',
                $activity->title,
                $row->scanned_at->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filename = 'attendance-'.$activity->id.'-'.Str::slug($activity->title).'.csv';

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="'.$filename.'"']);
    }

    private function financialReportData(Request $request): array
    {
        $mode = in_array($request->input('mode'), ['daily', 'monthly', 'annually'], true)
            ? $request->input('mode')
            : 'monthly';
        $year = (int) $request->input('year', now()->year);
        $month = min(12, max(1, (int) $request->input('month', now()->month)));
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfMonth();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $transactions = Transaction::with('user')
            ->where('status', 'active')
            ->when($mode === 'daily', fn ($query) => $query->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()]))
            ->when($mode === 'monthly', fn ($query) => $query->whereYear('transaction_date', $year)->whereMonth('transaction_date', $month))
            ->when($mode === 'annually', fn ($query) => $query->whereYear('transaction_date', $year))
            ->orderBy('transaction_date')
            ->get();

        $chartItems = $this->financialChartItems($transactions, $mode, $year, $month, $from, $to);
        $income = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');

        return [
            'transactions' => $transactions,
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $income - $expenses,
            'transactionCount' => $transactions->count(),
            'mode' => $mode,
            'year' => $year,
            'month' => $month,
            'from' => $from,
            'to' => $to,
            'periodLabel' => $this->financialPeriodLabel($mode, $year, $month, $from, $to),
            'chartItems' => $chartItems,
            'highestChartValue' => max(1, $chartItems->max(fn (array $item) => max($item['income'], $item['expenses']))),
            'incomeCategories' => $this->categoryTotals($transactions, 'income'),
            'expenseCategories' => $this->categoryTotals($transactions, 'expense'),
        ];
    }

    private function financialChartItems($transactions, string $mode, int $year, int $month, Carbon $from, Carbon $to)
    {
        if ($mode === 'annually') {
            return collect(range(1, 12))->map(function (int $chartMonth) use ($transactions): array {
                $rows = $transactions->filter(fn (Transaction $transaction) => (int) $transaction->transaction_date->format('n') === $chartMonth);

                return [
                    'label' => Carbon::create()->month($chartMonth)->format('M'),
                    'income' => (float) $rows->where('type', 'income')->sum('amount'),
                    'expenses' => (float) $rows->where('type', 'expense')->sum('amount'),
                ];
            });
        }

        $start = $mode === 'monthly' ? Carbon::create($year, $month, 1)->startOfDay() : $from->copy();
        $end = $mode === 'monthly' ? $start->copy()->endOfMonth() : $to->copy();
        $days = min(62, $start->diffInDays($end) + 1);

        return collect(range(0, $days - 1))->map(function (int $dayOffset) use ($transactions, $start, $days): array {
            $date = $start->copy()->addDays($dayOffset);
            $rows = $transactions->filter(fn (Transaction $transaction) => $transaction->transaction_date->isSameDay($date));

            return [
                'label' => $days > 20 ? $date->format('d') : $date->format('d M'),
                'income' => (float) $rows->where('type', 'income')->sum('amount'),
                'expenses' => (float) $rows->where('type', 'expense')->sum('amount'),
            ];
        });
    }

    private function financialPeriodLabel(string $mode, int $year, int $month, Carbon $from, Carbon $to): string
    {
        return match ($mode) {
            'daily' => $from->format('d M Y').' - '.$to->format('d M Y'),
            'annually' => (string) $year,
            default => Carbon::create($year, $month, 1)->format('F Y'),
        };
    }

    private function categoryTotals($transactions, string $type)
    {
        return $transactions
            ->where('type', $type)
            ->groupBy('category')
            ->map(fn ($rows, string $category) => ['category' => $category, 'total' => (float) $rows->sum('amount')])
            ->sortByDesc('total')
            ->take(5)
            ->values();
    }
}
