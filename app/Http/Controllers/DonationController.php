<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Donation;
use App\Models\PortalNotification;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DonationController extends Controller
{
    private const TYPES = ['Sambutan Ahli Keluarga', 'Kelahiran Anak', 'Kecemasan Perubatan', 'Bencana atau Kemalangan', 'Pendidikan Anak'];

    public function index(Request $request): View
    {
        $query = Donation::with('user', 'treasurerVerifier', 'reviewer')->latest();
        if (! $request->user()->hasRole('treasurer', 'admin')) {
            $query->where('user_id', $request->user()->id);
        }
        $query->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category));

        return view('donations.index', ['donations' => $query->paginate(15)->withQueryString(), 'types' => self::TYPES]);
    }

    public function create(): View
    {
        return view('donations.create', ['types' => self::TYPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:'.implode(',', self::TYPES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'request_date' => ['required', 'date', 'before_or_equal:today'],
        ], ['category.required' => 'Sila pilih jenis sumbangan.', 'request_date.required' => 'Sila pilih tarikh permohonan.']);

        $donation = Donation::create([...$data, 'user_id' => $request->user()->id, 'status' => 'pending']);
        $this->audit($donation, 'submitted', 'Submitted donation request.', $request);
        User::where('role', 'treasurer')->where('membership_status', 'active')->get()->each(fn (User $treasurer) => PortalNotification::create([
            'user_id' => $treasurer->id, 'title' => 'Permohonan sumbangan baharu',
            'message' => $request->user()->name.' memohon sumbangan '.$donation->category.'. Sila tetapkan had bajet kelab dan semak permohonan ini.',
            'type' => 'info', 'link' => route('donations.show', $donation),
        ]));

        return redirect()->route('donations.index')->with('status', 'Permohonan sumbangan dihantar kepada bendahari.');
    }

    public function show(Donation $donation): View
    {
        $this->authorizeAccess($donation);
        return view('donations.show', ['donation' => $donation->load('user', 'treasurerVerifier', 'reviewer', 'transaction')]);
    }

    public function verify(Request $request, Donation $donation): RedirectResponse
    {
        Gate::authorize('manage-finances');
        abort_unless($donation->status === 'pending', 422, 'Permohonan ini sudah diproses.');
        $data = $request->validate(['limit_amount' => ['required', 'numeric', 'min:0.01'], 'treasurer_notes' => ['nullable', 'string', 'max:1000']], ['limit_amount.required' => 'Sila tetapkan had sumbangan.', 'limit_amount.min' => 'Had sumbangan mesti lebih daripada RM0.00.']);
        abort_if($this->availableBalance() < (float) $data['limit_amount'], 422, 'Baki kelab tidak mencukupi untuk had sumbangan ini.');
        $donation->update(['status' => 'treasurer_verified', 'limit_amount' => $data['limit_amount'], 'treasurer_verified_by' => $request->user()->id, 'treasurer_notes' => $data['treasurer_notes'] ?? null, 'treasurer_verified_at' => now()]);
        $donation->loadMissing('user');
        User::where('role', 'admin')->where('membership_status', 'active')->get()->each(fn (User $admin) => PortalNotification::create([
            'user_id' => $admin->id, 'title' => 'Sumbangan menunggu kelulusan admin',
            'message' => 'Permohonan '.$donation->category.' oleh '.$donation->user->name.' telah disokong bendahari dengan had RM '.number_format((float) $donation->limit_amount, 2).'. Sila tetapkan amaun dan luluskan.',
            'type' => 'info', 'link' => route('donations.show', $donation),
        ]));
        PortalNotification::create(['user_id' => $donation->user_id, 'title' => 'Permohonan sumbangan disokong bendahari', 'message' => 'Permohonan '.$donation->category.' disokong dan menunggu kelulusan admin.', 'type' => 'info', 'link' => route('donations.show', $donation)]);
        $this->audit($donation, 'verified', 'Treasurer set donation limit.', $request);
        return back()->with('status', 'Had sumbangan ditetapkan dan dihantar kepada admin.');
    }

    public function approve(Request $request, Donation $donation): RedirectResponse
    {
        Gate::authorize('approve-expenses');
        abort_unless($donation->status === 'treasurer_verified', 422, 'Sumbangan perlu disokong bendahari dahulu.');
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'review_notes' => ['nullable', 'string', 'max:1000']], ['amount.required' => 'Sila masukkan amaun sumbangan.']);
        abort_if((float) $data['amount'] > (float) $donation->limit_amount, 422, 'Amaun tidak boleh melebihi had bendahari.');
        abort_if($this->availableBalance() < (float) $data['amount'], 422, 'Baki kelab tidak mencukupi untuk amaun ini.');
        $transaction = Transaction::create(['user_id' => $donation->user_id, 'type' => 'expense', 'amount' => $data['amount'], 'description' => 'Approved donation: '.$donation->category, 'receipt_number' => $this->receiptNumber(), 'transaction_date' => now()->toDateString(), 'category' => 'Sumbangan - '.$donation->category, 'payment_method' => 'Donation', 'status' => 'active']);
        $donation->update(['status' => 'approved', 'amount' => $data['amount'], 'reviewed_by' => $request->user()->id, 'transaction_id' => $transaction->id, 'review_notes' => $data['review_notes'] ?? null, 'reviewed_at' => now()]);
        PortalNotification::create(['user_id' => $donation->user_id, 'title' => 'Sumbangan diluluskan', 'message' => 'Permohonan '.$donation->category.' diluluskan sebanyak RM '.number_format((float) $donation->amount, 2).'.', 'type' => 'success', 'link' => route('donations.show', $donation)]);
        $this->audit($donation, 'approved', 'Approved donation and created expense transaction.', $request);
        return back()->with('status', 'Sumbangan diluluskan dan baki kelab telah dikurangkan.');
    }

    public function reject(Request $request, Donation $donation): RedirectResponse
    {
        Gate::authorize('approve-expenses');
        abort_unless(in_array($donation->status, ['pending', 'treasurer_verified'], true), 422);
        $data = $request->validate(['review_notes' => ['required', 'string', 'max:1000']], ['review_notes.required' => 'Sila isi sebab penolakan.']);
        $donation->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'review_notes' => $data['review_notes'], 'reviewed_at' => now()]);
        PortalNotification::create(['user_id' => $donation->user_id, 'title' => 'Permohonan sumbangan ditolak', 'message' => 'Permohonan '.$donation->category.' ditolak: '.$data['review_notes'], 'type' => 'warning', 'link' => route('donations.show', $donation)]);
        return back()->with('status', 'Permohonan sumbangan ditolak.');
    }

    private function authorizeAccess(Donation $donation): void { abort_unless(auth()->user()->hasRole('treasurer', 'admin') || $donation->user_id === auth()->id(), 403); }
    private function availableBalance(): float { return (float) SystemSetting::getValue('opening_balance', '0') + (float) Transaction::where('status', 'active')->where('type', 'income')->sum('amount') - (float) Transaction::where('status', 'active')->where('type', 'expense')->sum('amount'); }
    private function receiptNumber(): string { return SystemSetting::getValue('receipt_prefix', 'PB').'-'.now()->format('YmdHis').'-'.random_int(100, 999); }
    private function audit(Donation $donation, string $action, string $description, Request $request): void { AuditLog::create(['user_id' => $request->user()->id, 'action' => $action, 'module' => 'Donation', 'record_type' => Donation::class, 'record_id' => $donation->id, 'description' => $description, 'changes' => ['status' => $donation->status, 'limit_amount' => $donation->limit_amount, 'amount' => $donation->amount], 'ip_address' => $request->ip()]); }
}
