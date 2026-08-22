<?php

namespace App\Console\Commands;

use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Console\Command;

class SendFeeReminders extends Command
{
    protected $signature = 'fee:remind';

    protected $description = 'Create in-app reminders for members with outstanding fees.';

    public function handle(): int
    {
        User::where('fee_balance', '>', 0)->where('membership_status', 'active')->each(function (User $user): void {
            PortalNotification::firstOrCreate([
                'user_id' => $user->id,
                'title' => 'Peringatan tunggakan yuran',
            ], [
                'message' => 'Baki yuran anda ialah RM '.number_format((float) $user->fee_balance, 2).'.',
                'type' => 'fee',
                'link' => route('dashboard'),
            ]);
        });

        return self::SUCCESS;
    }
}
