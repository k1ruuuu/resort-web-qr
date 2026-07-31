<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // SECURITY FIX: Check environment variable to enable/disable HTTPS enforcement
        $forceHttps = config('app.force_https', false);
        
        // Only enforce HTTPS when explicitly enabled (production should have FORCE_HTTPS=true)
        if ($forceHttps && !$request->secure()) {
            // SECURITY FIX: Validate redirect URL to prevent open redirect
            $uri = $request->getRequestUri();
            
            // Block potentially malicious redirects
            if (preg_match('/(\/\/|@|%2f%2f|%40)/i', $uri)) {
                \Log::warning('[SECURITY] Blocked suspicious HTTPS redirect', [
                    'uri' => $uri,
                    'ip' => $request->ip(),
                ]);
                abort(400, 'Invalid request');
            }
            
            return redirect()->secure($uri, 301);
        }

        return $next($request);
    }
}
