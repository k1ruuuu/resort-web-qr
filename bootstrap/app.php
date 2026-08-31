<?php

use App\Http\Middleware\AuditRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Illuminate\Http\Middleware\TrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // SECURITY FIX: Only trust specific proxies, not all
        // Configure specific proxy IPs in production (e.g., load balancer IPs) or * for all load balancers (AWS ELB/Cloudflare)
        $trustedProxies = env('TRUSTED_PROXIES', null);
        if ($trustedProxies === '*') {
            $middleware->trustProxies(at: '*');
        } elseif ($trustedProxies) {
            $middleware->trustProxies(at: explode(',', $trustedProxies));
        } elseif (env('APP_ENV') === 'local') {
            // Only trust all proxies in local development by default
            $middleware->trustProxies(at: '*');
        }

        // Security middleware
        $middleware->append(\App\Http\Middleware\AttackDetectionMiddleware::class);
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        $middleware->append(\App\Http\Middleware\ForceHttpsMiddleware::class);

        // SECURITY FIX: Validate Host header against APP_URL (no-op in local env).
        // Prevents host-header injection / open redirect via redirect()->secure().
        $middleware->trustHosts();

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'audit' => AuditRequest::class,
            'ip.whitelist' => \App\Http\Middleware\IpWhitelistMiddleware::class,
            'validate.upload' => \App\Http\Middleware\ValidateFileUpload::class,
            'attack.detection' => \App\Http\Middleware\AttackDetectionMiddleware::class,
            'validate.pagination' => \App\Http\Middleware\ValidatePaginationParameters::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // L-16: API clients get a JSON 401 instead of an HTML 302 redirect to /login
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        // 419 CSRF mismatch: usually a stale login form (opened before logout / back button).
        // Never show the dead-end page — send the user back to a fresh login.
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expired. Please refresh and try again.'], 419);
            }

            return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
        });
    })->create();
