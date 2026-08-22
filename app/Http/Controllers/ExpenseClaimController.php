<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ExpenseClaim;
use App\Models\PortalNotification;
use App\Models\SystemSetting;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExpenseClaimController extends Controller
{
    public function index(Request $request): View
    {
        $query = ExpenseClaim::with('user', 'reviewer', 'treasurerVerifier', 'transaction')->latest();

        if (! $request->user()->hasRole('treasurer', 'chairman', 'admin')) {
            $query->where('user_id', $request->user()->id);
        }

        $query->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('category'), fn ($query) => $query->where('category', 'like', '%'.$request->category.'%'))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('claim_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('claim_date', '<=', $request->to));

        return view('claims.index', ['claims' => $query->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('claims.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['required', 'string', 'max:120'],
            'claim_date' => ['required', 'date'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $claim = ExpenseClaim::create([
            ...collect($data)->except('receipt')->all(),
            'user_id' => $request->user()->id,
            'receipt_path' => $request->file('receipt')->store('expense-claims', 'public'),
            'status' => 'pending',
        ]);

        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'submitted', 'module' => 'Expense Claim', 'record_type' => ExpenseClaim::class, 'record_id' => $claim->id, 'description' => 'Submitted expense claim '.$claim->title.'.', 'changes' => $claim->only(['title', 'amount', 'category', 'status']), 'ip_address' => $request->ip()]);

        return redirect()->route('claims.index')->with('status', 'Tuntutan perbelanjaan dihantar untuk semakan.');
    }

    public function show(ExpenseClaim $claim): View
    {
        $this->authorizeClaimAccess($claim);

        return view('claims.show', ['claim' => $claim->load('user', 'reviewer', 'treasurerVerifier', 'transaction'), 'availableBalance' => $this->availableBalance()]);
    }

    public function verify(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        Gate::authorize('view-financial-reports');
        abort_unless($claim->status === 'pending', 422);

        $data = $request->validate(['treasurer_notes' => ['nullable', 'string', 'max:1000']]);
        $claim->update(['status' => 'treasurer_verified', 'treasurer_verified_by' => $request->user()->id, 'treasurer_notes' => $data['treasurer_notes'] ?? null, 'treasurer_verified_at' => now()]);
        PortalNotification::create(['user_id' => $claim->user_id, 'title' => 'Tuntutan disahkan bendahari', 'message' => 'Tuntutan '.$claim->title.' telah disahkan dan menunggu kelulusan pengerusi.', 'type' => 'info', 'link' => route('claims.show', $claim)]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'verified', 'module' => 'Expense Claim', 'record_type' => ExpenseClaim::class, 'record_id' => $claim->id, 'description' => 'Treasurer verified expense claim '.$claim->title.'.', 'changes' => ['status' => 'treasurer_verified'], 'ip_address' => $request->ip()]);

        return redirect()->route('claims.show', $claim)->with('status', 'Tuntutan disahkan dan dihantar untuk kelulusan pengerusi.');
    }

    public function approve(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        Gate::authorize('approve-expenses');
        abort_unless($claim->status === 'treasurer_verified', 422, 'Tuntutan perlu disahkan bendahari dahulu.');
        abort_if($this->availableBalance() < (float) $claim->amount, 422, 'Baki kelab tidak mencukupi untuk meluluskan tuntutan ini.');

        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);
        $transaction = Transaction::create(['user_id' => $claim->user_id, 'type' => 'expense', 'amount' => $claim->amount, 'description' => 'Approved expense claim: '.$claim->title, 'receipt_number' => $this->receiptNumber(), 'transaction_date' => $claim->claim_date, 'category' => $claim->category, 'payment_method' => 'Claim', 'status' => 'active']);
        $claim->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'transaction_id' => $transaction->id, 'review_notes' => $data['review_notes'] ?? null, 'reviewed_at' => now()]);
        PortalNotification::create(['user_id' => $claim->user_id, 'title' => 'Tuntutan diluluskan', 'message' => 'Tuntutan '.$claim->title.' telah diluluskan.', 'type' => 'success', 'link' => route('claims.show', $claim)]);

        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'approved', 'module' => 'Expense Claim', 'record_type' => ExpenseClaim::class, 'record_id' => $claim->id, 'description' => 'Approved expense claim and generated expense '.$transaction->receipt_number.'.', 'changes' => ['status' => 'approved', 'transaction_id' => $transaction->id], 'ip_address' => $request->ip()]);

        return redirect()->route('claims.show', $claim)->with('status', 'Tuntutan diluluskan dan transaksi perbelanjaan dijana.');
    }

    public function reject(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        Gate::authorize('approve-expenses');
        abort_unless(in_array($claim->status, ['pending', 'treasurer_verified'], true), 422);

        $data = $request->validate(['review_notes' => ['required', 'string', 'max:1000']]);
        $claim->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'review_notes' => $data['review_notes'], 'reviewed_at' => now()]);
        PortalNotification::create(['user_id' => $claim->user_id, 'title' => 'Tuntutan ditolak', 'message' => 'Tuntutan '.$claim->title.' ditolak: '.$data['review_notes'], 'type' => 'warning', 'link' => route('claims.show', $claim)]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'rejected', 'module' => 'Expense Claim', 'record_type' => ExpenseClaim::class, 'record_id' => $claim->id, 'description' => 'Rejected expense claim '.$claim->title.'.', 'changes' => ['status' => 'rejected', 'reason' => $data['review_notes']], 'ip_address' => $request->ip()]);

        return redirect()->route('claims.show', $claim)->with('status', 'Tuntutan ditolak.');
    }

    public function receipt(ExpenseClaim $claim)
    {
        $this->authorizeClaimAccess($claim);
        abort_unless(Storage::disk('public')->exists($claim->receipt_path), 404);

        return Storage::disk('public')->response($claim->receipt_path);
    }

    private function authorizeClaimAccess(ExpenseClaim $claim): void
    {
        abort_unless(auth()->user()->hasRole('treasurer', 'chairman', 'admin') || $claim->user_id === auth()->id(), 403);
    }

    private function availableBalance(): float
    {
        $openingBalance = (float) SystemSetting::getValue('opening_balance', '0');
        $income = (float) Transaction::where('status', 'active')->where('type', 'income')->sum('amount');
        $expenses = (float) Transaction::where('status', 'active')->where('type', 'expense')->sum('amount');

        return $openingBalance + $income - $expenses;
    }

    private function receiptNumber(): string
    {
        $prefix = SystemSetting::getValue('receipt_prefix', 'PB');

        do {
            $receipt = $prefix.'-'.now()->format('YmdHis').'-'.random_int(100, 999);
        } while (Transaction::where('receipt_number', $receipt)->exists());

        return $receipt;
    }
}
