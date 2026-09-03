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

// Check for git updates daily at 01:00 WIB.
// Timezone is pinned explicitly to Asia/Jakarta because config('app.timezone')
// is UTC; without this, '01:00' would fire at 01:00 UTC (08:00 WIB).
Schedule::command('git:check-updates')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();

// Refresh the cached national-holiday table monthly. Holiday decrees change
// rarely (and only for future years), so monthly is plenty — the dashboard
// always reads the DB, never the API.
Schedule::command('holidays:fetch')
    ->monthlyOn(1, '02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();
