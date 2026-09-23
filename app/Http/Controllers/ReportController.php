<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Transaction;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function financial(Request $request): View
    {
        Gate::authorize('view-financial-reports');

        return view('reports.financial', $this->financialReportData($request));
    }

    public function financialPdf(Request $request): Response|View
    {
        Gate::authorize('view-financial-reports');

        $data = $this->financialReportData($request);

        if (! $request->boolean('download') && ! $request->boolean('render')) {
            $query = $request->except(['download', 'render']);

            return view('reports.download-preview', [
                'title' => 'Laporan Kewangan',
                'format' => 'pdf',
                'backUrl' => route('reports.financial', $query),
                'downloadUrl' => route('reports.financial.pdf', [...$query, 'download' => 1]),
                'inlineUrl' => route('reports.financial.pdf', [...$query, 'render' => 1]),
                'headers' => [],
                'rows' => [],
            ]);
        }

        $pdf = new Dompdf;
        $pdf->loadHtml(view('reports.financial_pdf', $data)->render());
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="laporan-kewangan.pdf"',
        ]);
    }

    public function financialCsv(Request $request): Response|View
    {
        Gate::authorize('view-financial-reports');

        $data = $this->financialReportData($request);
        $headers = ['Tarikh', 'Jenis', 'Kategori', 'Keterangan', 'Jumlah'];
        $rows = $data['transactions']->map(fn (Transaction $row) => [
            $row->transaction_date->format('d/m/Y'),
            $row->type,
            $row->category,
            $row->description,
            'RM '.number_format((float) $row->amount, 2),
        ])->values();

        if (! $request->boolean('download')) {
            $query = $request->except(['download']);

            return view('reports.download-preview', [
                'title' => 'Laporan Kewangan CSV',
                'format' => 'csv',
                'backUrl' => route('reports.financial', $query),
                'downloadUrl' => route('reports.financial.csv', [...$query, 'download' => 1]),
                'inlineUrl' => null,
                'headers' => $headers,
                'rows' => $rows,
            ]);
        }

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

    public function attendancePdf(Request $request): Response|View
    {
        Gate::authorize('view-financial-reports');

        $attendances = $this->attendanceReportRows($request);
        $query = $request->except(['download', 'render']);

        if (! $request->boolean('download') && ! $request->boolean('render')) {
            return view('reports.download-preview', [
                'title' => 'Laporan Kehadiran',
                'format' => 'pdf',
                'backUrl' => route('attendance.index', $query),
                'downloadUrl' => route('reports.attendance.pdf', [...$query, 'download' => 1]),
                'inlineUrl' => route('reports.attendance.pdf', [...$query, 'render' => 1]),
                'headers' => [],
                'rows' => [],
            ]);
        }

        $pdf = new Dompdf;
        $pdf->loadHtml(view('reports.attendance_pdf', compact('attendances'))->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="laporan-kehadiran.pdf"',
        ]);
    }

    public function attendanceCsv(Request $request): Response|View
    {
        Gate::authorize('view-financial-reports');

        $attendances = $this->attendanceReportRows($request);

        if (! $request->boolean('download')) {
            $query = $request->except(['download']);

            return view('reports.download-preview', [
                'title' => 'Laporan Kehadiran',
                'format' => 'csv',
                'backUrl' => route('attendance.index', $query),
                'downloadUrl' => route('reports.attendance.csv', [...$query, 'download' => 1]),
                'inlineUrl' => null,
                'headers' => ['Ahli', 'Aktiviti', 'Masa'],
                'rows' => $attendances->map(fn (Attendance $row) => [
                    $row->user->name,
                    $row->activity->title,
                    $row->scanned_at->format('d/m/Y h:i A'),
                ])->values(),
            ]);
        }

        $csv = "member,activity,scanned_at\n";
        foreach ($attendances as $row) {
            $csv .= sprintf("%s,%s,%s\n", str_replace(',', ' ', $row->user->name), str_replace(',', ' ', $row->activity->title), $row->scanned_at->format('Y-m-d H:i:s'));
        }

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="attendance-report.csv"']);
    }

    public function activityAttendanceCsv(Request $request, Activity $activity): Response|View
    {
        abort_unless(auth()->user()->hasRole('admin', 'treasurer') || $activity->created_by === auth()->id(), 403);
        abort_unless($activity->status === 'approved' && $activity->isFinished(), 422, 'Report hanya boleh dijana selepas aktiviti tamat.');

        $rows = Attendance::with('user')
            ->where('activity_id', $activity->id)
            ->orderBy('scanned_at')
            ->get();

        if (! $request->boolean('download')) {
            return view('reports.download-preview', [
                'title' => 'Kehadiran '.$activity->title,
                'format' => 'csv',
                'backUrl' => route('activities.show', $activity),
                'downloadUrl' => route('reports.activities.attendance.csv', ['activity' => $activity, 'download' => 1]),
                'inlineUrl' => null,
                'headers' => ['Ahli', 'E-mel', 'Jabatan', 'Aktiviti', 'Masa'],
                'rows' => $rows->map(fn (Attendance $row) => [
                    $row->user->name,
                    $row->user->email,
                    $row->user->department ?? '-',
                    $activity->title,
                    $row->scanned_at->format('d/m/Y h:i A'),
                ])->values(),
            ]);
        }

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

    public function activityReportPdf(Request $request, Activity $activity): Response|View
    {
        abort_unless(auth()->user()->hasRole('member', 'treasurer', 'admin'), 403);
        abort_unless($activity->status === 'approved' && $activity->isFinished(), 422, 'Report hanya boleh dijana selepas aktiviti tamat.');

        if (! $request->boolean('download') && ! $request->boolean('render')) {
            return view('reports.download-preview', [
                'title' => 'Laporan Aktiviti '.$activity->title,
                'format' => 'pdf',
                'backUrl' => route('activities.show', $activity),
                'downloadUrl' => route('reports.activities.pdf', ['activity' => $activity, 'download' => 1]),
                'inlineUrl' => route('reports.activities.pdf', ['activity' => $activity, 'render' => 1]),
                'headers' => [],
                'rows' => [],
            ]);
        }

        $activity->loadCount(['activeRegistrations', 'attendances']);
        $attendances = Attendance::with('user')->where('activity_id', $activity->id)->orderBy('scanned_at')->get();
        $reportPhoto = null;

        if ($activity->report_photo_path && Storage::disk('private')->exists($activity->report_photo_path)) {
            $mime = Storage::disk('private')->mimeType($activity->report_photo_path) ?: 'image/jpeg';
            $reportPhoto = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('private')->get($activity->report_photo_path));
        }

        $pdf = new Dompdf;
        $pdf->loadHtml(view('reports.activity_pdf', compact('activity', 'attendances', 'reportPhoto'))->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="activity-report-'.Str::slug($activity->title).'.pdf"',
        ]);
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

    private function attendanceReportRows(Request $request)
    {
        return Attendance::with('user', 'activity')
            ->when($request->filled('member'), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$request->member.'%')))
            ->when($request->filled('activity'), fn ($query) => $query->whereHas('activity', fn ($activityQuery) => $activityQuery->where('title', 'like', '%'.$request->activity.'%')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('scanned_at', $request->date))
            ->latest('scanned_at')
            ->get();
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
