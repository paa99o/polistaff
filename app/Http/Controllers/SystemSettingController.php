<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MemberFeeBill;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', ['settings' => [
            'club_name' => SystemSetting::getValue('club_name', 'PoliBest'),
            'monthly_fee' => SystemSetting::getValue('monthly_fee', '20'),
            'receipt_prefix' => SystemSetting::getValue('receipt_prefix', 'PB'),
            'contact_email' => SystemSetting::getValue('contact_email', 'admin@polibest.test'),
            'opening_balance' => SystemSetting::getValue('opening_balance', '0'),
        ]]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['club_name' => ['required', 'string', 'max:120'], 'monthly_fee' => ['required', 'numeric', 'min:0'], 'receipt_prefix' => ['required', 'string', 'max:10'], 'contact_email' => ['required', 'email'], 'opening_balance' => ['required', 'numeric']]);
        foreach ($data as $key => $value) {
            SystemSetting::setValue($key, $value);
        }
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'updated', 'module' => 'System Settings', 'description' => 'Updated system settings.', 'changes' => $data, 'ip_address' => $request->ip()]);

        return back()->with('status', 'Tetapan sistem dikemas kini.');
    }

    public function generateMonthlyFees(Request $request): RedirectResponse
    {
        $data = $request->validate(['billing_month' => ['nullable', 'date_format:Y-m']]);
        $fee = (float) SystemSetting::getValue('monthly_fee', '20');
        $billingMonth = Carbon::createFromFormat('Y-m-d', ($data['billing_month'] ?? now()->format('Y-m')).'-01')->startOfMonth();
        $created = 0;

        User::where('membership_status', 'active')->where('role', 'member')->orderBy('id')->chunkById(100, function ($users) use ($billingMonth, $fee, &$created): void {
            foreach ($users as $user) {
                $bill = MemberFeeBill::firstOrCreate(
                    ['user_id' => $user->id, 'billing_month' => $billingMonth->toDateTimeString()],
                    ['due_date' => $billingMonth->copy()->endOfMonth()->toDateString(), 'amount' => $fee, 'paid_amount' => 0, 'status' => 'unpaid']
                );

                if ($bill->wasRecentlyCreated) {
                    $user->increment('fee_balance', $fee);
                    $created++;
                }
            }
        });

        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'generated', 'module' => 'Monthly Fee Automation', 'description' => 'Generated monthly fees for active members.', 'changes' => ['billing_month' => $billingMonth->format('Y-m'), 'monthly_fee' => $fee, 'new_bills' => $created], 'ip_address' => $request->ip()]);

        return back()->with('status', 'Yuran '.$billingMonth->format('m/Y').' dijana untuk '.$created.' ahli. Rekod sedia ada tidak digandakan.');
    }
}
