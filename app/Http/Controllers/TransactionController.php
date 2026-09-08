<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-financial-reports');

        $transactions = Transaction::with('user')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('category'), fn ($query) => $query->where('category', 'like', '%'.$request->category.'%'))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('transaction_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('transaction_date', '<=', $request->to))
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        return view('transactions.index', ['transactions' => $transactions]);
    }

    public function create(): View
    {
        Gate::authorize('manage-finances');

        return view('transactions.create', ['users' => User::where('membership_status', 'active')->orderBy('name')->get()]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        Gate::authorize('manage-finances');

        $transaction = Transaction::create([...$request->validated(), 'receipt_number' => $this->receiptNumber(), 'status' => 'active']);
        $this->syncFeeBalance(null, $transaction);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'module' => 'Financial Transaction',
            'record_type' => Transaction::class,
            'record_id' => $transaction->id,
            'description' => 'Created '.$transaction->type.' transaction '.$transaction->receipt_number.'.',
            'changes' => $transaction->only(['type', 'amount', 'description', 'category', 'payment_method']),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('transactions.show', $transaction)->with('status', 'Transaksi berjaya direkodkan.');
    }

    public function show(Transaction $transaction): View
    {
        Gate::authorize('view-financial-reports');

        return view('transactions.receipt', compact('transaction'));
    }

    public function receiptPdf(Transaction $transaction): Response
    {
        Gate::authorize('view-financial-reports');

        $pdf = new Dompdf;
        $pdf->loadHtml(view('transactions.receipt_pdf', compact('transaction'))->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$transaction->receipt_number.'.pdf"',
        ]);
    }

    public function edit(Transaction $transaction): View
    {
        Gate::authorize('manage-finances');

        return view('transactions.edit', ['transaction' => $transaction, 'users' => User::orderBy('name')->get()]);
    }

    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('manage-finances');

        $before = $transaction->replicate();
        $beforeAttributes = $transaction->only(['type', 'amount', 'description', 'transaction_date', 'category', 'payment_method']);
        $transaction->update($request->validated());
        $this->syncFeeBalance($before, $transaction->fresh());

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'module' => 'Financial Transaction',
            'record_type' => Transaction::class,
            'record_id' => $transaction->id,
            'description' => 'Updated transaction '.$transaction->receipt_number.'.',
            'changes' => ['before' => $beforeAttributes, 'after' => $transaction->only(['type', 'amount', 'description', 'transaction_date', 'category', 'payment_method'])],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('transactions.index')->with('status', 'Transaksi dikemas kini.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('manage-finances');
        abort_unless($transaction->status === 'active', 422, 'Transaksi ini sudah dibatalkan.');

        $reason = request()->input('reversal_reason', 'Manual reversal');
        $before = $transaction->replicate();
        $transaction->update(['status' => 'reversed', 'reversed_by' => auth()->id(), 'reversed_at' => now(), 'reversal_reason' => $reason]);
        $this->syncFeeBalance($before, $transaction->fresh());

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'reversed',
            'module' => 'Financial Transaction',
            'record_type' => Transaction::class,
            'record_id' => $transaction->id,
            'description' => 'Reversed transaction '.$transaction->receipt_number.'.',
            'changes' => ['reason' => $reason, ...$transaction->only(['type', 'amount', 'description', 'category'])],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('status', 'Transaksi ditanda sebagai reversed.');
    }

    private function receiptNumber(): string
    {
        $prefix = SystemSetting::getValue('receipt_prefix', 'PB');

        do {
            $receipt = $prefix.'-'.now()->format('YmdHis').'-'.random_int(100, 999);
        } while (Transaction::where('receipt_number', $receipt)->exists());

        return $receipt;
    }

    private function syncFeeBalance(?Transaction $before, Transaction $after): void
    {
        $beforeReduction = $this->feeReduction($before);
        $afterReduction = $this->feeReduction($after);

        if ($beforeReduction > 0 && $before?->user_id) {
            User::whereKey($before->user_id)->increment('fee_balance', $beforeReduction);
        }

        if ($afterReduction > 0 && $after->user_id) {
            $user = User::find($after->user_id);
            if ($user) {
                $user->fee_balance = max(0, (float) $user->fee_balance - $afterReduction);
                $user->save();
            }
        }
    }

    private function feeReduction(?Transaction $transaction): float
    {
        if (! $transaction
            || $transaction->status !== 'active'
            || $transaction->type !== 'income'
            || ! $transaction->user_id
            || ! str_contains(strtolower((string) $transaction->category), 'yuran')) {
            return 0;
        }

        return (float) $transaction->amount;
    }
}
