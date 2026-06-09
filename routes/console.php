<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule voucher expiration check - runs every hour
Schedule::command('voucher:expire')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule pending voucher deliveries - runs every 5 minutes
Schedule::command('voucher:send-scheduled')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
