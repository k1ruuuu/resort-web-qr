<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        RateLimiter::for('voucher-redeem', function (Request $request) {
            return Limit::perMinute(config('voucher.redeem_rate_limit'))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('voucher-qr-scan', function (Request $request) {
            return Limit::perMinute(config('voucher.qr_rate_limit'))
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
