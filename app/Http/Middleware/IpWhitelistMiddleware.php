<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelistMiddleware
{
    /**
     * IP addresses allowed to access admin functions
     */
    protected array $whitelist = [];

    public function __construct()
    {
        // Load from environment variable
        $ips = env('ADMIN_IP_WHITELIST', '');
        $this->whitelist = $ips ? explode(',', $ips) : [];
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Skip in local environment
        if (app()->environment('local')) {
            return $next($request);
        }

        // If whitelist is empty, allow all (for easier setup)
        if (empty($this->whitelist)) {
            \Log::warning('Admin IP whitelist is empty. All IPs allowed.', [
                'ip' => $request->ip(),
                'route' => $request->path(),
            ]);
            return $next($request);
        }

        // Check if IP is whitelisted
        $clientIp = $request->ip();
        
        foreach ($this->whitelist as $allowedIp) {
            $allowedIp = trim($allowedIp);
            
            // Support CIDR notation
            if ($this->ipInRange($clientIp, $allowedIp)) {
                return $next($request);
            }
        }

        // Log unauthorized access attempt
        \Log::warning('Unauthorized admin access attempt blocked', [
            'ip' => $clientIp,
            'user_agent' => $request->userAgent(),
            'route' => $request->path(),
            'user_id' => $request->user()?->id,
        ]);

        abort(403, 'Access denied. Your IP address is not authorized.');
    }

    /**
     * Check if IP is in range (supports CIDR notation)
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        // Exact match
        if ($ip === $range) {
            return true;
        }

        // CIDR notation (e.g., 192.168.1.0/24)
        if (strpos($range, '/') !== false) {
            [$subnet, $mask] = explode('/', $range);
            
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int) $mask);
            
            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        return false;
    }
}
