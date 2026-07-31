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
        $ips = config('services.admin_ip_whitelist', '');
        $this->whitelist = $ips ? explode(',', $ips) : [];
    }

    public function handle(Request $request, Closure $next): Response
    {
        // SECURITY FIX: Remove local environment bypass - enforce whitelist in all environments
        // Whitelist bypass creates security vulnerability in staging/testing environments
        
        // If whitelist is empty, deny access by default (fail-secure approach)
        if (empty($this->whitelist)) {
            // Only log warning and allow if explicitly set in env to allow empty whitelist
            if (config('services.allow_empty_ip_whitelist', false)) {
                \Log::warning('Admin IP whitelist is empty but allowed by configuration.', [
                    'ip' => $request->ip(),
                    'route' => $request->path(),
                ]);
                return $next($request);
            }
            
            \Log::critical('[SECURITY] Admin access denied - IP whitelist not configured', [
                'ip' => $request->ip(),
                'route' => $request->path(),
                'user_id' => $request->user()?->id,
            ]);
            abort(403, 'Access denied. IP whitelist is not configured.');
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
     * Check if IP is in range (supports CIDR notation, IPv4 and IPv6)
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        // Exact match
        if ($ip === $range) {
            return true;
        }

        // CIDR notation (e.g., 192.168.1.0/24 or 2001:db8::/32)
        if (strpos($range, '/') !== false) {
            [$subnet, $maskStr] = explode('/', $range);

            // Non-numeric or malformed masks must not match anything
            if (!ctype_digit($maskStr)) {
                return false;
            }

            $mask = (int) $maskStr;
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);

            // Malformed IPs or non-CIDR entries must not match anything
            if ($ipBin === false || $subnetBin === false || $mask < 0) {
                return false;
            }

            $maxBits = strlen($subnetBin) * 8;

            // Mask outside the range for this IP version never matches
            if ($mask > $maxBits) {
                return false;
            }

            $fullBytes = intdiv($mask, 8);
            $remainingBits = $mask % 8;

            $maskBytes = str_repeat("\xff", $fullBytes);

            if ($remainingBits > 0) {
                $maskBytes .= chr(0xff << (8 - $remainingBits));
            }

            $maskBytes .= str_repeat("\x00", strlen($subnetBin) - strlen($maskBytes));

            for ($i = 0; $i < strlen($subnetBin); $i++) {
                if ((($ipBin[$i] ?? "\x00") & $maskBytes[$i]) !== ($subnetBin[$i] & $maskBytes[$i])) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
