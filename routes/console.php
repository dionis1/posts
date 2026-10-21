<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dispatch due articles once per minute; the queued job checks status and version
// again so repeat deliveries or cancelled schedules cannot publish stale content.
Schedule::command('posts:publish-due')->everyMinute()->withoutOverlapping();
