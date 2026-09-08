<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('activity:remind')->hourly();
Schedule::command('fees:monthly-cycle')->monthlyOn(1, '08:00');
Schedule::command('backup:cleanup')->dailyAt('02:30')->withoutOverlapping();

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
