<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function financial(Request $request): View
    {
        Gate::authorize('view-financial-reports');

        $year = (int) $request->input('year', now()->year);
        $month = $request->filled('month') ? (int) $request->month : null;

        $transactions = Transaction::with('user')
            ->whereYear('transaction_date', $year)
            ->where('status', 'active')
            ->when($month, fn ($query) => $query->whereMonth('transaction_date', $month))
            ->orderBy('transaction_date')
            ->get();

        return view('reports.financial', [
            'transactions' => $transactions,
            'income' => $transactions->where('type', 'income')->sum('amount'),
            'expenses' => $transactions->where('type', 'expense')->sum('amount'),
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function financialPdf(Request $request): Response
    {
        Gate::authorize('view-financial-reports');

        $year = (int) $request->input('year', now()->year);
        $month = $request->filled('month') ? (int) $request->month : null;
        $transactions = Transaction::with('user')
            ->whereYear('transaction_date', $year)
            ->where('status', 'active')
            ->when($month, fn ($query) => $query->whereMonth('transaction_date', $month))
            ->orderBy('transaction_date')
            ->get();
        $income = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');

        $pdf = new Dompdf();
        $pdf->loadHtml(view('reports.financial_pdf', compact('transactions', 'income', 'expenses', 'year', 'month'))->render());
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="laporan-kewangan.pdf"',
        ]);
    }

    public function financialCsv(): Response
    {
        Gate::authorize('view-financial-reports');

        $csv = "date,type,category,description,amount\n";
        foreach (Transaction::where('status', 'active')->orderBy('transaction_date')->get() as $row) {
            $csv .= sprintf("%s,%s,%s,%s,%s\n", $row->transaction_date->format('Y-m-d'), $row->type, $row->category, str_replace(',', ' ', $row->description), $row->amount);
        }

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="financial-report.csv"']);
    }

    public function attendanceCsv(): Response
    {
        Gate::authorize('view-financial-reports');

        $csv = "member,activity,scanned_at\n";
        foreach (\App\Models\Attendance::with('user', 'activity')->latest('scanned_at')->get() as $row) {
            $csv .= sprintf("%s,%s,%s\n", str_replace(',', ' ', $row->user->name), str_replace(',', ' ', $row->activity->title), $row->scanned_at->format('Y-m-d H:i:s'));
        }

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="attendance-report.csv"']);
    }
}
