<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\PortalNotification;
use Illuminate\Console\Command;

class SendActivityReminders extends Command
{
    protected $signature = 'activity:remind';

    protected $description = 'Create in-app reminders for activities happening within 24 hours.';

    public function handle(): int
    {
        Activity::whereBetween('date_time', [now(), now()->addDay()])->where('status', 'approved')->each(function (Activity $activity): void {
            PortalNotification::firstOrCreate([
                'title' => 'Peringatan aktiviti: '.$activity->title,
                'link' => route('activities.show', $activity),
            ], [
                'message' => 'Aktiviti akan berlangsung pada '.$activity->date_time->format('d/m/Y h:i A').'.',
                'type' => 'activity',
            ]);
        });

        return self::SUCCESS;
    }
}
