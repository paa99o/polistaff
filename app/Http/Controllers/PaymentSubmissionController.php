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
use App\Services\EmailAuditService;
use App\Services\EmailDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentSubmissionController extends Controller
{
    public function __construct(private EmailAuditService $emailAuditService, private EmailDeliveryService $emailDeliveryService) {}

    public function index(Request $request): View
    {
        $query = PaymentSubmission::with('user', 'reviewer', 'transaction')->latest();

        if (! $request->user()->hasRole('treasurer', 'chairman', 'admin')) {
            $query->where('user_id', $request->user()->id);
        }

        $query->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('payment_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('payment_date', '<=', $request->to));

        return view('payments.index', ['payments' => $query->paginate(15)->withQueryString()]);
    }

    public function statement(Request $request): View
    {
        $user = $request->user();

        return view('payments.statement', [
            'bills' => MemberFeeBill::where('user_id', $user->id)->latest('billing_month')->paginate(18),
            'payments' => PaymentSubmission::with('transaction')->where('user_id', $user->id)->latest('payment_date')->limit(12)->get(),
            'balance' => $user->fee_balance,
        ]);
    }

    public function create(): View
    {
        return view('payments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:120'],
            'payment_date' => ['required', 'date'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.required' => 'Sila isi jumlah bayaran.',
            'amount.min' => 'Jumlah bayaran mesti sekurang-kurangnya RM 0.01.',
            'payment_method.required' => 'Sila pilih kaedah bayaran.',
            'payment_date.required' => 'Sila pilih tarikh bayaran.',
            'proof.required' => 'Sila upload fail bukti bayaran.',
            'proof.mimes' => 'Bukti bayaran mesti dalam format JPG, PNG atau PDF.',
            'proof.max' => 'Bukti bayaran tidak boleh melebihi 4MB.',
        ]);

        $path = $request->file('proof')->store('payment-proofs', 'public');

        $payment = PaymentSubmission::create([
            'user_id' => $request->user()->id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_date' => $data['payment_date'],
            'proof_path' => $path,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

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
        ]);
    }

    public function resubmit(Request $request, PaymentSubmission $payment): RedirectResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        abort_unless($payment->status === 'rejected', 422, 'Hanya bayaran yang ditolak boleh dihantar semula.');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:120'],
            'payment_date' => ['required', 'date'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.required' => 'Sila isi jumlah bayaran.',
            'amount.min' => 'Jumlah bayaran mesti sekurang-kurangnya RM 0.01.',
            'payment_method.required' => 'Sila pilih kaedah bayaran.',
            'payment_date.required' => 'Sila pilih tarikh bayaran.',
            'proof.required' => 'Sila upload fail bukti bayaran baharu.',
            'proof.mimes' => 'Bukti bayaran mesti dalam format JPG, PNG atau PDF.',
            'proof.max' => 'Bukti bayaran tidak boleh melebihi 4MB.',
        ]);

        $oldProofPath = $payment->proof_path;
        $newProofPath = $request->file('proof')->store('payment-proofs', 'public');

        $payment->update([
            ...collect($data)->except('proof')->all(),
            'proof_path' => $newProofPath,
            'status' => 'pending',
            'reviewed_by' => null,
            'review_notes' => null,
            'reviewed_at' => null,
            'transaction_id' => null,
            'allocated_amount' => 0,
        ]);

        Storage::disk('public')->delete($oldProofPath);

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

        abort_unless(Storage::disk('public')->exists($payment->proof_path), 404);

        return Storage::disk('public')->response($payment->proof_path);
    }

    private function authorizePaymentAccess(PaymentSubmission $payment): void
    {
        abort_unless(
            auth()->user()->hasRole('treasurer', 'chairman', 'admin') || $payment->user_id === auth()->id(),
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

    private function timelineLogs(PaymentSubmission $payment)
    {
        if (! auth()->user()->hasRole('treasurer', 'chairman', 'admin')) {
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
