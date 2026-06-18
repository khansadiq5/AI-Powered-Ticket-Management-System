<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fetch inbound support emails and convert to tickets every minute
Schedule::command('tickets:fetch-emails')->everyMinute()->withoutOverlapping();
