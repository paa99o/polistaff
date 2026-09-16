<?php

namespace App\Services;

use App\Models\MemberFeeBill;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;

class MonthlyFeeService
{
    /**
     * Make sure a member has a bill for every month from their first known
     * bill (or membership start) through the requested month.
     */
    public function ensureThrough(User $user, ?Carbon $through = null): int
    {
        $through = ($through ?? now())->copy()->startOfMonth();
        $firstBill = $user->feeBills()->oldest('billing_month')->first();
        $start = $firstBill?->billing_month?->copy()->startOfMonth()
            ?? ($user->joined_date ? Carbon::parse($user->joined_date)->startOfMonth() : $through);
        $fee = (float) SystemSetting::getValue('monthly_fee', '20');
        $created = 0;

        for ($month = $start->copy(); $month->lte($through); $month->addMonth()) {
            $bill = MemberFeeBill::firstOrCreate(
                ['user_id' => $user->id, 'billing_month' => $month->toDateString()],
                ['due_date' => $month->copy()->endOfMonth()->toDateString(), 'amount' => $fee, 'paid_amount' => 0, 'status' => 'unpaid']
            );

            if ($bill->wasRecentlyCreated) {
                $user->increment('fee_balance', $fee);
                $created++;
            }
        }

        return $created;
    }

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
