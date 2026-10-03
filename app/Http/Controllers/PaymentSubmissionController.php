<?php

namespace App\Http\Controllers;

use App\Mail\PaymentApprovedMail;
use App\Mail\PaymentRejectedMail;
use App\Models\AuditLog;
use App\Models\MemberFeeBill;
use App\Models\PaymentSubmission;
use App\Models\PortalNotification;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\EmailAuditService;
use App\Services\EmailDeliveryService;
use App\Services\FinancialHistoryDeletionService;
use App\Services\MonthlyFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentSubmissionController extends Controller
{
    private const PAYMENT_PACKAGE_RATE = 10;

    public function __construct(private EmailAuditService $emailAuditService, private EmailDeliveryService $emailDeliveryService) {}

    public function index(Request $request): View
    {
        $query = PaymentSubmission::with('user', 'reviewer', 'transaction')->latest();

        $canViewAllPayments = $request->user()->hasRole('treasurer', 'admin', 'chairman');
        if (! $canViewAllPayments) {
            $query->where('user_id', $request->user()->id);
        } elseif ($canViewAllPayments && $request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $query->when(
            in_array($request->query('status'), ['pending', 'approved', 'rejected', 'cancelled'], true),
            fn ($query) => $query->where('status', $request->query('status')),
        )
            ->when($request->filled('from'), fn ($query) => $query->whereDate('payment_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('payment_date', '<=', $request->to));

        $user = $request->user();
        $isFinanceManager = $user->hasRole('treasurer', 'admin');
        if (! $canViewAllPayments) {
            app(MonthlyFeeService::class)->ensureThrough($user);
        }
        $bills = $canViewAllPayments
            ? collect()
            : MemberFeeBill::where('user_id', $user->id)->orderBy('billing_month')->get();
        [$availableBills] = $canViewAllPayments
            ? [collect()]
            : $this->availableBillsForPayment($user);
        $currentYear = now()->year;
        $currentMonth = now()->startOfMonth();
        $recentApprovedPayments = PaymentSubmission::where('user_id', $user->id)
            ->where('status', 'approved')
            ->latest('created_at')
            ->get();
        $paymentDatesByBill = collect();
        foreach ($recentApprovedPayments as $approvedPayment) {
            foreach ($approvedPayment->bill_ids ?? [] as $billId) {
                $paymentDatesByBill->put($billId, $approvedPayment->created_at);
            }
        }
        $paidBills = $bills->filter(fn (MemberFeeBill $bill) => $bill->status === 'paid')->map(function (MemberFeeBill $bill) use ($paymentDatesByBill, $recentApprovedPayments) {
            $bill->payment_datetime = $paymentDatesByBill->get($bill->id) ?? $recentApprovedPayments->first()?->created_at;
            return $bill;
        });
        return view('payments.index', [
            'payments' => $query->paginate(15)->withQueryString(),
            'isFinanceManager' => $isFinanceManager,
            'canViewAllPayments' => $canViewAllPayments,
            'pendingPaymentCount' => $isFinanceManager
                ? PaymentSubmission::where('status', 'pending')->whereHas('user', fn ($query) => $query->where('role', 'member'))->count()
                : 0,
            'collectionThisMonth' => $isFinanceManager
                ? Transaction::where('type', 'income')->where('status', 'active')->where('category', 'like', '%Yuran%')->whereYear('transaction_date', now()->year)->whereMonth('transaction_date', now()->month)->sum('amount')
                : 0,
            'outstandingTotal' => $isFinanceManager
                ? User::where('role', 'member')->where('membership_status', 'active')->sum('fee_balance')
                : 0,
            'overdueBills' => $availableBills->filter(fn (MemberFeeBill $bill) => $bill->billing_month->year < $currentYear),
            'currentUnpaidBills' => $availableBills->filter(fn (MemberFeeBill $bill) => $bill->billing_month->year === $currentYear && $bill->billing_month->lte($currentMonth)),
            'recentApprovedPayments' => $recentApprovedPayments->filter(fn (PaymentSubmission $payment) => $payment->payment_date->year === $currentYear),
            'paidBills' => $paidBills,
        ]);
    }

    public function statement(Request $request, MonthlyFeeService $monthlyFeeService): View
    {
        $user = $request->user();
        $monthlyFeeService->ensureThrough($user);

        return view('payments.statement', [
            'bills' => MemberFeeBill::where('user_id', $user->id)->latest('billing_month')->paginate(18),
            'payments' => PaymentSubmission::with('transaction')->where('user_id', $user->id)->latest('payment_date')->limit(12)->get(),
            'balance' => $user->fee_balance,
        ]);
    }

    public function create(Request $request, MonthlyFeeService $monthlyFeeService): View
    {
        abort_unless($request->user()->hasRole('member'), 403);
        $user = $request->user();
        $monthlyFeeService->ensureThrough($user);

        [$bills, $pendingAmount, $outstanding] = $this->availableBillsForPayment($user);
        $latestBillingMonth = MemberFeeBill::where('user_id', $user->id)->max('billing_month');
        $nextBillingMonth = $latestBillingMonth
            ? \Illuminate\Support\Carbon::parse($latestBillingMonth)->addMonth()->startOfMonth()
            : now()->startOfMonth();
        return view('payments.create', [
            'bills' => $bills,
            'overdueBills' => $bills->filter(fn (MemberFeeBill $bill): bool => $bill->due_date?->isPast() && ! $bill->due_date?->isToday()),
            'outstanding' => $outstanding,
            'pendingAmount' => (float) $pendingAmount,
            'nextBillingMonth' => $nextBillingMonth->translatedFormat('F Y'),
            'monthlyBillAmount' => (float) SystemSetting::getValue('monthly_fee', '20'),
            'paymentMonthOptions' => [1 => 10, 3 => 30, 6 => 60, 12 => 120],
            'paymentOptions' => $this->paymentOptions(),
            'financePaymentQrPath' => SystemSetting::getValue('finance_payment_qr_path'),
            'financeBankDetails' => [
                'bank_name' => SystemSetting::getValue('finance_bank_name'),
                'account_name' => SystemSetting::getValue('finance_account_name'),
                'account_number' => SystemSetting::getValue('finance_account_number'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('member'), 403);
        $data = $request->validate([
            'months' => ['required', 'integer', 'in:1,3,6,12'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:120'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'months.required' => 'Sila pilih tempoh bayaran yuran.',
            'months.in' => 'Tempoh bayaran yang dipilih tidak sah.',
            'amount.required' => 'Sila isi jumlah bayaran.',
            'amount.min' => 'Jumlah bayaran mesti sekurang-kurangnya RM 0.01.',
            'payment_method.required' => 'Sila pilih kaedah bayaran.',
            'proof.mimes' => 'Bukti bayaran mesti dalam format JPG, PNG atau PDF.',
            'proof.max' => 'Bukti bayaran tidak boleh melebihi 4MB.',
        ]);

        $expectedAmount = (int) $data['months'] * self::PAYMENT_PACKAGE_RATE;
        if (abs((float) $data['amount'] - $expectedAmount) > 0.005) {
            return back()->withInput()->withErrors(['amount' => 'Jumlah bayaran mesti sepadan dengan pilihan tempoh (RM 10 sebulan).']);
        }

        $user = $request->user();
        $monthlyFeeService = app(MonthlyFeeService::class);
        $monthlyFeeService->ensureThrough($user);
        [$availableBills, , $available] = $this->availableBillsForPayment($user);

        // Create future monthly bills on demand so members can prepay months ahead.
        // Existing unpaid bills remain first in the allocation order.
        $latestBillingMonth = MemberFeeBill::where('user_id', $user->id)->max('billing_month');
        $nextMonth = $latestBillingMonth
            ? now()->parse($latestBillingMonth)->addMonth()->startOfMonth()
            : now()->startOfMonth();
        $monthsToCreate = 0;
        while ($available + 0.005 < (float) $data['amount'] && $monthsToCreate < 24) {
            $monthsToCreate++;
            $monthlyFeeService->ensureThrough($user, $nextMonth->copy());
            [$availableBills, , $available] = $this->availableBillsForPayment($user);
            $latestBillingMonth = MemberFeeBill::where('user_id', $user->id)->max('billing_month');
            if ($latestBillingMonth) {
                $nextMonth = \Illuminate\Support\Carbon::parse($latestBillingMonth)->addMonth()->startOfMonth();
            }
        }
        if ((float) $data['amount'] > $available + 0.005) {
            return back()->withInput()->withErrors([
                'amount' => 'Sistem tidak dapat menyediakan bil bulanan yang mencukupi untuk pakej ini. Sila hubungi bendahari.',
            ]);
        }

        $path = $request->hasFile('proof')
            ? $request->file('proof')->store('payment-proofs', 'private')
            : null;

        $remainingToAllocate = (float) $data['amount'];
        $targetBills = collect();
        foreach ($availableBills as $bill) {
            if ($remainingToAllocate <= 0.005) {
                break;
            }

            $targetBills->push($bill);
            $remainingToAllocate -= min($remainingToAllocate, (float) $bill->payment_available_amount);
        }

        $packageNote = 'Pakej bayaran '.$data['months'].' bulan (RM '.number_format(self::PAYMENT_PACKAGE_RATE, 2).' sebulan).';
        $notes = trim(($data['notes'] ?? '').' '.$packageNote);
        if ($targetBills->isNotEmpty()) {
            $notes .= ' Agihan bermula: '.$targetBills->map(fn (MemberFeeBill $bill) => $bill->billing_month->format('m/Y'))->join(', ').'.';
        }

        $payment = PaymentSubmission::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_date' => now()->toDateString(),
            'proof_path' => $path,
            'notes' => $notes,
            'bill_ids' => $targetBills->pluck('id')->values()->all(),
            'status' => 'pending',
        ]);

        $paidMonths = $targetBills->isNotEmpty()
            ? $targetBills->map(fn (MemberFeeBill $bill) => $bill->billing_month->translatedFormat('F Y'))->join(', ')
            : 'tunggakan yuran terawal';
        User::where('role', 'treasurer')->where('membership_status', 'active')->get()->each(function (User $treasurer) use ($payment, $request, $paidMonths): void {
            PortalNotification::create([
                'user_id' => $treasurer->id,
                'title' => 'Bukti bayaran yuran baharu',
                'message' => $request->user()->name.' telah menghantar bukti bayaran yuran untuk '.$paidMonths.' berjumlah RM '.number_format((float) $payment->amount, 2).'. Sila semak dan luluskan bayaran ini.',
                'type' => 'info',
                'link' => route('payments.show', $payment),
            ]);
        });

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'submitted',
            'module' => 'Payment Proof',
            'record_type' => PaymentSubmission::class,
            'record_id' => $payment->id,
            'description' => 'Submitted payment proof for RM '.number_format((float) $payment->amount, 2).'.',
            'changes' => $payment->only(['amount', 'payment_method', 'payment_date', 'status']),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('payments.index')->with('status', 'Bukti bayaran berjaya dihantar dan menunggu semakan bendahari.');
    }

    public function show(PaymentSubmission $payment): View
    {
        $this->authorizePaymentAccess($payment);

        return view('payments.show', [
            'payment' => $payment->load('user', 'reviewer', 'transaction'),
            'timelineLogs' => $this->timelineLogs($payment),
            'paymentOptions' => $this->paymentOptions(),
        ]);
    }

    public function resubmit(Request $request, PaymentSubmission $payment): RedirectResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        abort_unless($payment->status === 'rejected', 422, 'Hanya bayaran yang ditolak boleh dihantar semula.');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:120'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.required' => 'Sila isi jumlah bayaran.',
            'amount.min' => 'Jumlah bayaran mesti sekurang-kurangnya RM 0.01.',
            'payment_method.required' => 'Sila pilih kaedah bayaran.',
            'proof.required' => 'Sila upload fail bukti bayaran baharu.',
            'proof.mimes' => 'Bukti bayaran mesti dalam format JPG, PNG atau PDF.',
            'proof.max' => 'Bukti bayaran tidak boleh melebihi 4MB.',
        ]);

        $outstanding = (float) MemberFeeBill::where('user_id', $payment->user_id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get()->sum(fn (MemberFeeBill $bill): float => $bill->remainingAmount());
        $pendingAmount = (float) PaymentSubmission::where('user_id', $payment->user_id)
            ->where('status', 'pending')->where('id', '!=', $payment->id)->sum('amount');
        if ((float) $data['amount'] > max(0, $outstanding - $pendingAmount) + 0.005) {
            return back()->withInput()->withErrors(['amount' => 'Jumlah bayaran melebihi tunggakan yang tersedia. Bayaran akan mengikut bulan paling lama dahulu.']);
        }

        $oldProofPath = $payment->proof_path;
        $newProofPath = $request->file('proof')->store('payment-proofs', 'private');

        $payment->update([
            ...collect($data)->except('proof')->all(),
            'payment_date' => now()->toDateString(),
            'proof_path' => $newProofPath,
            'status' => 'pending',
            'reviewed_by' => null,
            'review_notes' => null,
            'reviewed_at' => null,
            'transaction_id' => null,
            'allocated_amount' => 0,
        ]);

        Storage::disk('private')->delete($oldProofPath);

        $this->auditPaymentAction($payment, 'resubmitted', 'Resubmitted rejected payment proof for review.', $request);

        return redirect()->route('payments.show', $payment)->with('status', 'Bukti bayaran baharu berjaya dihantar dan menunggu semakan.');
    }

    public function cancel(Request $request, PaymentSubmission $payment): RedirectResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        abort_unless($payment->status === 'pending', 422, 'Hanya bayaran yang masih menunggu semakan boleh dibatalkan.');

        $payment->update(['status' => 'cancelled']);
        $this->auditPaymentAction($payment, 'cancelled', 'Cancelled pending payment proof.', $request);

        return redirect()->route('payments.index')->with('status', 'Penghantaran bukti bayaran telah dibatalkan.');
    }

    public function destroyHistory(Request $request, PaymentSubmission $payment, FinancialHistoryDeletionService $history): RedirectResponse
    {
        abort_unless($request->user()->hasRole('treasurer'), 403);

        $proofPath = $payment->proof_path;

        DB::transaction(function () use ($request, $payment, $history): void {
            $payment = PaymentSubmission::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $transaction = $payment->transaction_id
                ? Transaction::query()->whereKey($payment->transaction_id)->lockForUpdate()->first()
                : null;

            if ($payment->status === 'approved') {
                if (! $transaction || $transaction->status === 'active') {
                    $feeOwner = User::query()->whereKey($payment->user_id)->lockForUpdate()->first();
                    $feeOwner?->increment('fee_balance', (float) $payment->amount);
                }

                $billIds = $payment->bill_ids ?? [];
                $bills = MemberFeeBill::query()
                    ->where('user_id', $payment->user_id)
                    ->when($billIds !== [], fn ($query) => $query->whereIn('id', $billIds))
                    ->when($billIds === [], fn ($query) => $query->where('paid_amount', '>', 0))
                    ->orderByDesc('billing_month')
                    ->lockForUpdate()
                    ->get();

                $remaining = (float) $payment->allocated_amount;
                if ($remaining <= 0) {
                    $remaining = (float) $payment->amount;
                }

                foreach ($bills as $bill) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $restored = min($remaining, (float) $bill->paid_amount);
                    if ($restored <= 0) {
                        continue;
                    }

                    $bill->paid_amount = max(0, (float) $bill->paid_amount - $restored);
                    $bill->status = (float) $bill->paid_amount > 0
                        ? 'partial'
                        : ($bill->due_date?->lt(today()) ? 'overdue' : 'unpaid');
                    $bill->save();
                    $remaining -= $restored;
                }
            }

            $history->deleteRelatedMessages($payment, '/payments/'.$payment->id);
            $history->deleteTransactionAndReversals($transaction);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'deleted',
                'module' => 'Payment Proof',
                'record_type' => PaymentSubmission::class,
                'record_id' => $payment->id,
                'description' => 'Treasurer deleted payment history #'.$payment->id.'.',
                'changes' => ['user_id' => $payment->user_id, 'amount' => $payment->amount, 'status' => $payment->status, 'transaction_id' => $payment->transaction_id],
                'ip_address' => $request->ip(),
            ]);

            $payment->delete();
        });

        if ($proofPath) {
            Storage::disk('private')->delete($proofPath);
        }

        return redirect()->route('payments.index')->with('status', 'Sejarah bayaran yuran dipadam. Bil dan baki ahli yang berkaitan telah dikemas kini.');
    }

    public function approve(Request $request, PaymentSubmission $payment): RedirectResponse
    {
        Gate::authorize('manage-finances');
        abort_unless($payment->status === 'pending', 422, 'Bayaran ini sudah disemak.');

        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']], [
            'review_notes.max' => 'Catatan semakan tidak boleh melebihi 1000 aksara.',
        ]);

        [$transaction, $allocated] = DB::transaction(function () use ($payment, $request, $data): array {
            $transaction = Transaction::create([
                'user_id' => $payment->user_id,
                'type' => 'income',
                'amount' => $payment->amount,
                'description' => 'Bayaran yuran ahli melalui bukti bayaran #'.$payment->id,
                'receipt_number' => $this->receiptNumber(),
                'transaction_date' => $payment->payment_date,
                'category' => 'Yuran',
                'payment_method' => $payment->payment_method,
                'status' => 'active',
            ]);

            $allocated = $this->allocatePaymentToBills($payment);

            $payment->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'transaction_id' => $transaction->id,
                'allocated_amount' => $allocated,
                'review_notes' => $data['review_notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            $user = $payment->user()->lockForUpdate()->first();
            $user->fee_balance = max(0, (float) $user->fee_balance - (float) $payment->amount);
            $user->save();

            return [$transaction, $allocated];
        });

        PortalNotification::create(['user_id' => $payment->user_id, 'title' => 'Bayaran diluluskan', 'message' => 'Bayaran RM '.number_format((float) $payment->amount, 2).' telah diluluskan.', 'type' => 'success', 'link' => route('payments.show', $payment)]);

        $payment->loadMissing('user', 'transaction');

        if ($payment->user->email && $payment->user->wantsEmail('finance')) {
            $this->emailDeliveryService->send($payment->user, 'payment approved', new PaymentApprovedMail($payment), $payment);
            $this->emailAuditService->sent($payment->user, 'payment approved', $payment);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'approved',
            'module' => 'Payment Proof',
            'record_type' => PaymentSubmission::class,
            'record_id' => $payment->id,
            'description' => 'Approved payment proof and generated receipt '.$transaction->receipt_number.'.',
            'changes' => ['payment_status' => 'approved', 'transaction_id' => $transaction->id, 'amount' => $payment->amount, 'allocated_amount' => $allocated],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('payments.show', $payment)->with('status', 'Bayaran diluluskan dan resit transaksi dijana. Emel diproses mengikut tetapan ahli.');
    }

    public function reject(Request $request, PaymentSubmission $payment): RedirectResponse
    {
        Gate::authorize('manage-finances');
        abort_unless($payment->status === 'pending', 422, 'Bayaran ini sudah disemak.');

        $data = $request->validate(['review_notes' => ['required', 'string', 'max:1000']], [
            'review_notes.required' => 'Sila isi sebab bayaran ditolak.',
            'review_notes.max' => 'Sebab ditolak tidak boleh melebihi 1000 aksara.',
        ]);

        $payment->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'review_notes' => $data['review_notes'],
            'reviewed_at' => now(),
        ]);

        PortalNotification::create(['user_id' => $payment->user_id, 'title' => 'Bayaran ditolak', 'message' => 'Bayaran RM '.number_format((float) $payment->amount, 2).' ditolak: '.$data['review_notes'], 'type' => 'warning', 'link' => route('payments.show', $payment)]);

        $payment->loadMissing('user');

        if ($payment->user->email && $payment->user->wantsEmail('finance')) {
            $this->emailDeliveryService->send($payment->user, 'payment rejected', new PaymentRejectedMail($payment), $payment);
            $this->emailAuditService->sent($payment->user, 'payment rejected', $payment);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'rejected',
            'module' => 'Payment Proof',
            'record_type' => PaymentSubmission::class,
            'record_id' => $payment->id,
            'description' => 'Rejected payment proof #'.$payment->id.'.',
            'changes' => ['payment_status' => 'rejected', 'review_notes' => $data['review_notes']],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('payments.show', $payment)->with('status', 'Bayaran ditolak dengan catatan semakan. Emel diproses mengikut tetapan ahli.');
    }

    public function proof(PaymentSubmission $payment)
    {
        $this->authorizePaymentAccess($payment);

        abort_unless($payment->proof_path && Storage::disk('private')->exists($payment->proof_path), 404);

        return Storage::disk('private')->response($payment->proof_path);
    }

    private function authorizePaymentAccess(PaymentSubmission $payment): void
    {
        abort_unless(
            auth()->user()->hasRole('treasurer', 'admin', 'chairman') || $payment->user_id === auth()->id(),
            403
        );
    }

    private function receiptNumber(): string
    {
        $prefix = SystemSetting::getValue('receipt_prefix', 'PB');

        do {
            $receipt = $prefix.'-'.now()->format('YmdHis').'-'.random_int(100, 999);
        } while (Transaction::where('receipt_number', $receipt)->exists());

        return $receipt;
    }

    private function allocatePaymentToBills(PaymentSubmission $payment): float
    {
        $remaining = (float) $payment->amount;
        $allocated = 0.0;

        MemberFeeBill::where('user_id', $payment->user_id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->when(! empty($payment->bill_ids), fn ($query) => $query->whereIn('id', $payment->bill_ids))
            ->orderBy('billing_month')
            ->lockForUpdate()
            ->get()
            ->each(function (MemberFeeBill $bill) use (&$remaining, &$allocated): void {
                if ($remaining <= 0) {
                    return;
                }

                $applied = min($remaining, $bill->remainingAmount());
                $bill->paid_amount = (float) $bill->paid_amount + $applied;
                $bill->status = $bill->remainingAmount() <= 0 ? 'paid' : 'partial';
                $bill->save();

                $remaining -= $applied;
                $allocated += $applied;
            });

        return $allocated;
    }

    /** @return array{0: \Illuminate\Support\Collection, 1: float, 2: float} */
    private function availableBillsForPayment(User $user): array
    {
        $bills = MemberFeeBill::where('user_id', $user->id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->orderBy('billing_month')
            ->get();
        $pendingPayments = PaymentSubmission::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->get(['amount', 'bill_ids']);
        $reserved = [];

        foreach ($pendingPayments as $payment) {
            $remaining = (float) $payment->amount;
            $selectedIds = collect($payment->bill_ids ?? [])->map(fn ($id) => (int) $id)->all();
            $targets = $selectedIds
                ? $bills->whereIn('id', $selectedIds)->sortBy('billing_month')
                : $bills;

            foreach ([$targets, $bills] as $allocationTarget) {
                foreach ($allocationTarget as $bill) {
                    if ($remaining <= 0.005) {
                        break;
                    }
                    $billId = (int) $bill->id;
                    $freeAmount = max(0, (float) $bill->remainingAmount() - ($reserved[$billId] ?? 0));
                    $allocation = min($remaining, $freeAmount);
                    $reserved[$billId] = ($reserved[$billId] ?? 0) + $allocation;
                    $remaining -= $allocation;
                }
                if ($remaining <= 0.005) {
                    break;
                }
            }
        }

        $availableTotal = 0.0;
        $availableBills = $bills->filter(function (MemberFeeBill $bill) use (&$reserved, &$availableTotal): bool {
            $available = round(max(0, (float) $bill->remainingAmount() - ($reserved[(int) $bill->id] ?? 0)), 2);
            $bill->setAttribute('payment_available_amount', $available);
            $availableTotal += $available;
            return $available > 0;
        })->values();

        return [$availableBills, (float) $pendingPayments->sum('amount'), round($availableTotal, 2)];
    }

    private function paymentOptions(): array
    {
        $options = ['Cash' => 'Tunai kepada bendahari'];
        if (SystemSetting::getValue('finance_payment_qr_path')) {
            $options['DuitNow QR'] = 'QR bayaran kelab';
        }
        if (SystemSetting::getValue('finance_bank_name') && SystemSetting::getValue('finance_account_name') && SystemSetting::getValue('finance_account_number')) {
            $options['Transfer'] = 'Pindahan bank';
        }

        return $options;
    }

    private function timelineLogs(PaymentSubmission $payment)
    {
        if (! auth()->user()->hasRole('treasurer', 'admin')) {
            return collect();
        }

        return AuditLog::with('user')
            ->where('record_type', PaymentSubmission::class)
            ->where('record_id', $payment->id)
            ->oldest()
            ->get();
    }

    private function auditPaymentAction(PaymentSubmission $payment, string $action, string $description, Request $request): void
    {
        \App\Models\AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'module' => 'Payment Proof',
            'record_type' => PaymentSubmission::class,
            'record_id' => $payment->id,
            'description' => $description,
            'changes' => ['status' => $payment->status],
            'ip_address' => $request->ip(),
        ]);
    }
}
