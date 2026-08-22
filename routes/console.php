<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('activity:remind')->hourly();
Schedule::command('fee:remind')->dailyAt('08:00');

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
