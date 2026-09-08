<?php

namespace App\Http\Controllers;

use App\Mail\ExpenseClaimApprovedMail;
use App\Mail\ExpenseClaimRejectedMail;
use App\Mail\ExpenseClaimVerifiedMail;
use App\Models\AuditLog;
use App\Models\ExpenseClaim;
use App\Models\PortalNotification;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Services\EmailAuditService;
use App\Services\EmailDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExpenseClaimController extends Controller
{
    public function __construct(private EmailAuditService $emailAuditService, private EmailDeliveryService $emailDeliveryService) {}

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
        return view('claims.create', ['claim' => null, 'resubmission' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateClaim($request, true);

        $claim = ExpenseClaim::create([
            ...collect($data)->except('receipt')->all(),
            'user_id' => $request->user()->id,
            'receipt_path' => $request->file('receipt')->store('expense-claims', 'public'),
            'status' => 'pending',
        ]);

        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'submitted', 'module' => 'Expense Claim', 'record_type' => ExpenseClaim::class, 'record_id' => $claim->id, 'description' => 'Submitted expense claim '.$claim->title.'.', 'changes' => $claim->only(['title', 'amount', 'category', 'status']), 'ip_address' => $request->ip()]);

        return redirect()->route('claims.index')->with('status', 'Tuntutan perbelanjaan dihantar untuk semakan.');
    }

    public function edit(ExpenseClaim $claim): View
    {
        $this->authorizeOwnerAction($claim, 'pending');

        return view('claims.create', ['claim' => $claim, 'resubmission' => false]);
    }

    public function update(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        $this->authorizeOwnerAction($claim, 'pending');
        $data = $this->validateClaim($request, false);

        $oldReceiptPath = $claim->receipt_path;
        $newReceiptPath = $request->hasFile('receipt')
            ? $request->file('receipt')->store('expense-claims', 'public')
            : $oldReceiptPath;

        $claim->update([
            ...collect($data)->except('receipt')->all(),
            'receipt_path' => $newReceiptPath,
        ]);

        if ($newReceiptPath !== $oldReceiptPath) {
            Storage::disk('public')->delete($oldReceiptPath);
        }

        $this->auditClaimAction($claim, 'updated', 'Updated pending expense claim.', $request);

        return redirect()->route('claims.show', $claim)->with('status', 'Tuntutan berjaya dikemas kini.');
    }

    public function destroy(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        $this->authorizeOwnerAction($claim, 'pending');

        Storage::disk('public')->delete($claim->receipt_path);
        $this->auditClaimAction($claim, 'cancelled', 'Cancelled pending expense claim.', $request);
        $claim->delete();

        return redirect()->route('claims.index')->with('status', 'Tuntutan pending telah dibatalkan.');
    }

    public function resubmitForm(ExpenseClaim $claim): View
    {
        $this->authorizeOwnerAction($claim, 'rejected');

        return view('claims.create', ['claim' => $claim, 'resubmission' => true]);
    }

    public function resubmit(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        $this->authorizeOwnerAction($claim, 'rejected');
        $data = $this->validateClaim($request, true);
        $oldReceiptPath = $claim->receipt_path;
        $newReceiptPath = $request->file('receipt')->store('expense-claims', 'public');

        $claim->update([
            ...collect($data)->except('receipt')->all(),
            'receipt_path' => $newReceiptPath,
            'status' => 'pending',
            'reviewed_by' => null,
            'treasurer_verified_by' => null,
            'transaction_id' => null,
            'treasurer_notes' => null,
            'treasurer_verified_at' => null,
            'review_notes' => null,
            'reviewed_at' => null,
        ]);

        Storage::disk('public')->delete($oldReceiptPath);
        $this->auditClaimAction($claim, 'resubmitted', 'Resubmitted rejected expense claim for review.', $request);

        return redirect()->route('claims.show', $claim)->with('status', 'Tuntutan berjaya dihantar semula untuk semakan.');
    }

    public function show(ExpenseClaim $claim): View
    {
        $this->authorizeClaimAccess($claim);

        return view('claims.show', [
            'claim' => $claim->load('user', 'reviewer', 'treasurerVerifier', 'transaction'),
            'availableBalance' => $this->availableBalance(),
            'timelineLogs' => $this->timelineLogs($claim),
        ]);
    }

    public function verify(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        Gate::authorize('manage-finances');
        abort_unless($claim->status === 'pending', 422);

        $data = $request->validate(['treasurer_notes' => ['nullable', 'string', 'max:1000']], [
            'treasurer_notes.max' => 'Catatan bendahari tidak boleh melebihi 1000 aksara.',
        ]);
        $claim->update(['status' => 'treasurer_verified', 'treasurer_verified_by' => $request->user()->id, 'treasurer_notes' => $data['treasurer_notes'] ?? null, 'treasurer_verified_at' => now()]);
        PortalNotification::create(['user_id' => $claim->user_id, 'title' => 'Tuntutan disahkan bendahari', 'message' => 'Tuntutan '.$claim->title.' telah disahkan dan menunggu kelulusan pengerusi.', 'type' => 'info', 'link' => route('claims.show', $claim)]);
        $claim->loadMissing('user');

        if ($claim->user->email && $claim->user->wantsEmail('finance')) {
            $this->emailDeliveryService->send($claim->user, 'expense claim verified', new ExpenseClaimVerifiedMail($claim), $claim);
            $this->emailAuditService->sent($claim->user, 'expense claim verified', $claim);
        }

        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'verified', 'module' => 'Expense Claim', 'record_type' => ExpenseClaim::class, 'record_id' => $claim->id, 'description' => 'Treasurer verified expense claim '.$claim->title.'.', 'changes' => ['status' => 'treasurer_verified'], 'ip_address' => $request->ip()]);

        return redirect()->route('claims.show', $claim)->with('status', 'Tuntutan disahkan dan dihantar untuk kelulusan pengerusi. Emel diproses mengikut tetapan ahli.');
    }

    public function approve(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        Gate::authorize('approve-expenses');
        abort_unless($claim->status === 'treasurer_verified', 422, 'Tuntutan perlu disahkan bendahari dahulu.');
        abort_if($this->availableBalance() < (float) $claim->amount, 422, 'Baki kelab tidak mencukupi untuk meluluskan tuntutan ini.');

        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']], [
            'review_notes.max' => 'Catatan kelulusan tidak boleh melebihi 1000 aksara.',
        ]);
        $transaction = Transaction::create(['user_id' => $claim->user_id, 'type' => 'expense', 'amount' => $claim->amount, 'description' => 'Approved expense claim: '.$claim->title, 'receipt_number' => $this->receiptNumber(), 'transaction_date' => $claim->claim_date, 'category' => $claim->category, 'payment_method' => 'Claim', 'status' => 'active']);
        $claim->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'transaction_id' => $transaction->id, 'review_notes' => $data['review_notes'] ?? null, 'reviewed_at' => now()]);
        PortalNotification::create(['user_id' => $claim->user_id, 'title' => 'Tuntutan diluluskan', 'message' => 'Tuntutan '.$claim->title.' telah diluluskan.', 'type' => 'success', 'link' => route('claims.show', $claim)]);
        $claim->loadMissing('user', 'transaction');

        if ($claim->user->email && $claim->user->wantsEmail('finance')) {
            $this->emailDeliveryService->send($claim->user, 'expense claim approved', new ExpenseClaimApprovedMail($claim), $claim);
            $this->emailAuditService->sent($claim->user, 'expense claim approved', $claim);
        }

        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'approved', 'module' => 'Expense Claim', 'record_type' => ExpenseClaim::class, 'record_id' => $claim->id, 'description' => 'Approved expense claim and generated expense '.$transaction->receipt_number.'.', 'changes' => ['status' => 'approved', 'transaction_id' => $transaction->id], 'ip_address' => $request->ip()]);

        return redirect()->route('claims.show', $claim)->with('status', 'Tuntutan diluluskan dan transaksi perbelanjaan dijana. Emel diproses mengikut tetapan ahli.');
    }

    public function reject(Request $request, ExpenseClaim $claim): RedirectResponse
    {
        Gate::authorize('approve-expenses');
        abort_unless(in_array($claim->status, ['pending', 'treasurer_verified'], true), 422);

        $data = $request->validate(['review_notes' => ['required', 'string', 'max:1000']], [
            'review_notes.required' => 'Sila isi sebab tuntutan ditolak.',
            'review_notes.max' => 'Sebab ditolak tidak boleh melebihi 1000 aksara.',
        ]);
        $claim->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'review_notes' => $data['review_notes'], 'reviewed_at' => now()]);
        PortalNotification::create(['user_id' => $claim->user_id, 'title' => 'Tuntutan ditolak', 'message' => 'Tuntutan '.$claim->title.' ditolak: '.$data['review_notes'], 'type' => 'warning', 'link' => route('claims.show', $claim)]);
        $claim->loadMissing('user');

        if ($claim->user->email && $claim->user->wantsEmail('finance')) {
            $this->emailDeliveryService->send($claim->user, 'expense claim rejected', new ExpenseClaimRejectedMail($claim), $claim);
            $this->emailAuditService->sent($claim->user, 'expense claim rejected', $claim);
        }

        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'rejected', 'module' => 'Expense Claim', 'record_type' => ExpenseClaim::class, 'record_id' => $claim->id, 'description' => 'Rejected expense claim '.$claim->title.'.', 'changes' => ['status' => 'rejected', 'reason' => $data['review_notes']], 'ip_address' => $request->ip()]);

        return redirect()->route('claims.show', $claim)->with('status', 'Tuntutan ditolak. Emel diproses mengikut tetapan ahli.');
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

    private function authorizeOwnerAction(ExpenseClaim $claim, string $status): void
    {
        abort_unless($claim->user_id === auth()->id(), 403);
        abort_unless($claim->status === $status, 422, 'Tindakan ini tidak tersedia untuk status tuntutan semasa.');
    }

    private function validateClaim(Request $request, bool $receiptRequired): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['required', 'string', 'max:120'],
            'claim_date' => ['required', 'date'],
            'receipt' => [$receiptRequired ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'title.required' => 'Sila isi tajuk tuntutan.',
            'amount.required' => 'Sila isi jumlah tuntutan.',
            'amount.min' => 'Jumlah tuntutan mesti sekurang-kurangnya RM 0.01.',
            'category.required' => 'Sila isi kategori tuntutan.',
            'claim_date.required' => 'Sila pilih tarikh tuntutan.',
            'receipt.required' => 'Sila upload resit tuntutan.',
            'receipt.mimes' => 'Resit tuntutan mesti dalam format JPG, PNG atau PDF.',
            'receipt.max' => 'Resit tuntutan tidak boleh melebihi 4MB.',
        ]);
    }

    private function auditClaimAction(ExpenseClaim $claim, string $action, string $description, Request $request): void
    {
        AuditLog::create(['user_id' => $request->user()->id, 'action' => $action, 'module' => 'Expense Claim', 'record_type' => ExpenseClaim::class, 'record_id' => $claim->id, 'description' => $description, 'changes' => ['status' => $claim->status], 'ip_address' => $request->ip()]);
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

    private function timelineLogs(ExpenseClaim $claim)
    {
        if (! auth()->user()->hasRole('treasurer', 'chairman', 'admin')) {
            return collect();
        }

        return AuditLog::with('user')
            ->where('record_type', ExpenseClaim::class)
            ->where('record_id', $claim->id)
            ->oldest()
            ->get();
    }
}
