<?php

namespace App\Services;

use App\Models\MemberFeeBill;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;

class MonthlyFeeService
{
    public function generate(?string $month = null): array
    {
        $fee = (float) SystemSetting::getValue('monthly_fee', '20');
        $billingMonth = Carbon::createFromFormat('Y-m-d', ($month ?? now()->format('Y-m')).'-01')->startOfMonth();
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

        return [
            'billing_month' => $billingMonth,
            'monthly_fee' => $fee,
            'new_bills' => $created,
        ];
    }
}
