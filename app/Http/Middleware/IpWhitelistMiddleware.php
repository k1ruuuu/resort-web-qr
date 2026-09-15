<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelistMiddleware
{
    /**
     * Intentionally disabled fail-open. Do NOT rely on this as a security control.
     * Re-enable ADMIN_IP_WHITELIST enforcement here if IP restriction is needed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // IP Whitelist functionality has been disabled per user request
        return $next($request);
    }
}
