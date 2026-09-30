<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$logPath = storage_path('logs/scheduler.log');

// Daily maintenance: hard delete bookings past Expected Departure + 5h grace period (17:30 WIB), cancel no-show, expire vouchers
Schedule::command('daily:maintenance --all')
    ->dailyAt('17:30')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(30)
    ->appendOutputTo($logPath);

// Hourly maintenance sweep during operational hours (12:30 - 23:00 WIB) to clean up overdue checkouts
Schedule::command('daily:maintenance --all')
    ->hourly()
    ->between('12:30', '23:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(30)
    ->appendOutputTo($logPath);

// Schedule voucher expiration check - runs every hour
Schedule::command('voucher:expire')
    ->hourly()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(15)
    ->appendOutputTo($logPath);

// Schedule pending voucher deliveries - runs every 5 minutes
Schedule::command('voucher:send-scheduled')
    ->everyFiveMinutes()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(10)
    ->appendOutputTo($logPath);

// Daily database backup at 23:00 WIB
Schedule::command('db:backup')
    ->dailyAt('23:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(30)
    ->appendOutputTo($logPath);

