<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fetch currency rates 4x per day (every 6 hours)
Schedule::command('currency:fetch')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground();
