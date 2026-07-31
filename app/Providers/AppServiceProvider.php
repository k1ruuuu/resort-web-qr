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
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // M-17: never expose debug output outside local/testing environments
        if (config('app.debug') && !in_array(config('app.env'), ['local', 'testing'], true)) {
            config(['app.debug' => false]);
            \Illuminate\Support\Facades\Log::warning('APP_DEBUG was enabled outside local/testing and has been forced off.');
        }

        RateLimiter::for('voucher-redeem', function (Request $request) {
            return Limit::perMinute((int) config('voucher.redeem_rate_limit', 10))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('voucher-qr-scan', function (Request $request) {
            return Limit::perMinute((int) config('voucher.qr_rate_limit', 30))
                ->by($request->user()?->id ?: $request->ip());
        });

        // SECURITY FIX: Global limit for all authenticated API endpoints
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) env('API_RATE_LIMIT', 120))
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
