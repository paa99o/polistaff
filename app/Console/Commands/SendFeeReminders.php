<?php

namespace App\Console\Commands;

use App\Mail\FeeReminderMail;
use App\Models\PortalNotification;
use App\Models\User;
use App\Services\EmailAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendFeeReminders extends Command
{
    protected $signature = 'fee:remind';

    protected $description = 'Create in-app reminders for members with outstanding fees.';

    public function handle(EmailAuditService $emailAuditService): int
    {
        $sent = 0;

        User::where('fee_balance', '>', 0)->where('membership_status', 'active')->each(function (User $user) use ($emailAuditService, &$sent): void {
            PortalNotification::firstOrCreate([
                'user_id' => $user->id,
                'title' => 'Peringatan tunggakan yuran',
            ], [
                'message' => 'Baki yuran anda ialah RM '.number_format((float) $user->fee_balance, 2).'.',
                'type' => 'fee',
                'link' => route('dashboard'),
            ]);

            if ($user->email && $user->wantsEmail('fee_reminders')) {
                Mail::to($user->email)->send(new FeeReminderMail($user));
                $emailAuditService->sent($user, 'fee reminder', $user);
                $sent++;
            }
        });

        $this->info("Fee reminders processed. Emails sent: {$sent}");

        return self::SUCCESS;
    }
}
