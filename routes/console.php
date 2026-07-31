<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily maintenance: delete bookings past Expected Departure (12:30 WIB), cancel no-show, expire vouchers
Schedule::command('daily:maintenance --all')
    ->dailyAt('12:35')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();

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
