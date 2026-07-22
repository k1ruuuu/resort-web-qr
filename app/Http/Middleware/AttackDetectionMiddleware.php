<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AttackDetectionMiddleware
{
    /**
     * SQL Injection patterns
     */
    protected array $sqliPatterns = [
        '/(\bUNION\b.*\bSELECT\b)/i',
        '/(\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
        '/([\'"])\s*(OR|AND)\s*([\'"]|\d+)\s*(=|<|>)/i',
        '/(SLEEP\s*\(\s*\d+\s*\)|BENCHMARK\s*\([^)]+\)|WAITFOR\s+DELAY)/i',
        '/(INFORMATION_SCHEMA|SCHEMATA|TABLES|COLUMNS)/i',
        '/(\/\*!?.*?\*\/|--[^\n]*|#[^\n]*)/i',
        '/(EXEC\s*\(|EXECUTE\s*\(|sp_executesql)/i',
        '/(DROP\s+TABLE|ALTER\s+TABLE|TRUNCATE\s+TABLE|INSERT\s+INTO|DELETE\s+FROM)/i',
        '/(0x[0-9A-F]{2,}|CHAR\s*\(\s*\d+)/i',
        '/(\|\||CONCAT\s*\(|GROUP_CONCAT)/i',
        '/(LOAD_FILE|INTO\s+OUTFILE|INTO\s+DUMPFILE)/i',
    ];

    /**
     * XSS (Cross-Site Scripting) patterns
     */
    protected array $xssPatterns = [
        '/<script[^>]*>.*?<\/script>/i',
        '/javascript\s*:\s*/i',
        '/on\w+\s*=\s*[\'"]/', 
        '/<iframe[^>]*src\s*=/i',
        '/document\.(cookie|write)/i',
        '/eval\s*\(.*\)/i',
        '/<img[^>]+onerror\s*=/i',
        '/<svg[^>]+onload\s*=/i',
        '/(<object|<embed|<applet)/i',
        '/vbscript\s*:/i',
        '/(data:text\/html|data:application)/i',
    ];

    /**
     * IDOR (Insecure Direct Object Reference) patterns
     */
    protected array $idorPatterns = [
        '/\/api\/\w+\/\d{8,}\/(edit|delete|update)/i',
        '/(?:user_id|userId|id)=\d{5,}/i',
    ];

    /**
     * Path Traversal patterns
     */
    protected array $pathTraversalPatterns = [
        '/\.\.\//',
        '/\.\.\\\\/',
        '/%2e%2e%2f/i',
        '/%2e%2e\//i',
        '/\/etc\/passwd/i',
        '/\/proc\/self/i',
        '/c:\\\\windows/i',
        '/\/\.git\//i',
        '/\/\.env/i',
        '/%00/i',
    ];

    /**
     * Blocked user agents (scanners, bots, attack tools)
     */
    protected array $blockedUserAgents = [
        'sqlmap', 'nikto', 'nmap', 'masscan', 'acunetix',
        'burpsuite', 'netsparker', 'appscan', 'nessus', 'openvas',
        'gobuster', 'dirbuster', 'wfuzz', 'hydra', 'metasploit',
        'havij', 'beef', 'w3af', 'webscarab', 'paros',
        'ZmEu', 'libwww-perl',
    ];

    /**
     * Whitelisted paths (won't be checked for attacks)
     */
    protected array $whitelistedPaths = [
        '/',
        '/login',
        '/api/auth',
        '/api/health',
    ];

    /**
     * Rate limiting settings
     */
    protected int $rateLimitWindow = 10; // seconds
    protected int $rateLimitMax = 60; // max requests
    protected int $ddosThreshold = 200; // DDoS threshold
    protected int $blockTtl = 900; // 15 minutes

    public function handle(Request $request, Closure $next): Response
    {
        $clientIP = $this->getClientIP($request);
        $userAgent = $request->userAgent() ?? 'Unknown';
        $method = $request->method();
        $fullUrl = $request->fullUrl();
        $path = $request->path();
        $payload = $request->getQueryString() ?? '';

        // Skip checks for trusted IPs (localhost, private networks)
        if ($this->isTrustedIP($clientIP)) {
            return $next($request);
        }

        // Check rate limiting first
        $rateLimitResult = $this->checkRateLimit($clientIP);
        if ($rateLimitResult['blocked']) {
            return $this->getBlockPage(
                $rateLimitResult['isDDoS'] ? 'DDoS' : 'RATE_LIMIT',
                $clientIP,
                $fullUrl
            );
        }

        // Check malicious user agent
        if ($this->detectMaliciousUserAgent($userAgent)) {
            $this->logAttack('MALICIOUS_UA', $clientIP, $fullUrl, $userAgent, $method);
            return $this->getBlockPage('MALICIOUS_UA', $clientIP, $fullUrl);
        }

        // Skip pattern checks for whitelisted paths
        if (!$this->isWhitelistedPath($path)) {
            // SQL Injection detection
            if ($this->detectSQLi($fullUrl)) {
                $this->logAttack('SQLi', $clientIP, $fullUrl, $this->safeDecode($payload), $method);
                return $this->getBlockPage('SQLi', $clientIP, $fullUrl);
            }

            // XSS detection
            if ($this->detectXSS($fullUrl)) {
                $this->logAttack('XSS', $clientIP, $fullUrl, $this->safeDecode($payload), $method);
                return $this->getBlockPage('XSS', $clientIP, $fullUrl);
            }

            // IDOR detection
            if ($this->detectIDOR($path, $method)) {
                $this->logAttack('IDOR', $clientIP, $fullUrl, "Method: {$method} | Path: {$path}", $method);
                return $this->getBlockPage('IDOR', $clientIP, $fullUrl);
            }

            // Path Traversal detection
            if ($this->detectPathTraversal($fullUrl)) {
                $this->logAttack('PATH_TRAVERSAL', $clientIP, $fullUrl, $this->safeDecode($payload), $method);
                return $this->getBlockPage('PATH_TRAVERSAL', $clientIP, $fullUrl);
            }
        }

        return $next($request);
    }

    /**
     * Get client IP address
     */
    protected function getClientIP(Request $request): string
    {
        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            $ips = array_map('trim', explode(',', $forwarded));
            foreach ($ips as $ip) {
                if (!$this->isPrivateIP($ip)) {
                    return $ip;
                }
            }
            return $ips[0] ?? 'unknown';
        }

        return $request->header('X-Real-IP') ?? $request->ip() ?? 'unknown';
    }

    /**
     * Check if IP is private/internal
     */
    protected function isPrivateIP(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return str_starts_with($ip, 'fe80:') || $ip === '::1';
        }

        $longIP = ip2long($ip);
        if ($longIP === false) {
            return false;
        }

        return (
            ($longIP >= ip2long('10.0.0.0') && $longIP <= ip2long('10.255.255.255')) ||
            ($longIP >= ip2long('172.16.0.0') && $longIP <= ip2long('172.31.255.255')) ||
            ($longIP >= ip2long('192.168.0.0') && $longIP <= ip2long('192.168.255.255')) ||
            ($longIP >= ip2long('127.0.0.0') && $longIP <= ip2long('127.255.255.255'))
        );
    }

    /**
     * Check if IP is trusted (localhost, private networks)
     */
    protected function isTrustedIP(string $ip): bool
    {
        $trustedIPs = ['127.0.0.1', 'localhost', '::1'];
        return in_array($ip, $trustedIPs) || $this->isPrivateIP($ip);
    }

    /**
     * Safe URL decode (prevents double encoding attacks)
     */
    protected function safeDecode(string $payload): string
    {
        $decoded = $payload;
        for ($i = 0; $i < 2; $i++) {
            try {
                $next = urldecode($decoded);
                if ($next === $decoded) {
                    break;
                }
                $decoded = $next;
            } catch (\Exception $e) {
                break;
            }
        }
        return $decoded;
    }

    /**
     * Check if path is whitelisted
     */
    protected function isWhitelistedPath(string $path): bool
    {
        foreach ($this->whitelistedPaths as $whitelisted) {
            if ($whitelisted === '/' && $path === '/') {
                return true;
            }
            if ($path === $whitelisted || str_starts_with($path, $whitelisted . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test patterns against payload
     */
    protected function testPatterns(string $payload, array $patterns): bool
    {
        $decoded = $this->safeDecode($payload);
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $payload) || preg_match($pattern, $decoded)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect SQL Injection
     */
    protected function detectSQLi(string $payload): bool
    {
        return $this->testPatterns($payload, $this->sqliPatterns);
    }

    /**
     * Detect XSS
     */
    protected function detectXSS(string $payload): bool
    {
        return $this->testPatterns($payload, $this->xssPatterns);
    }

    /**
     * Detect IDOR
     */
    protected function detectIDOR(string $path, string $method): bool
    {
        if (!in_array($method, ['PUT', 'DELETE', 'PATCH', 'POST'])) {
            return false;
        }
        return $this->testPatterns($path, $this->idorPatterns);
    }

    /**
     * Detect Path Traversal
     */
    protected function detectPathTraversal(string $payload): bool
    {
        return $this->testPatterns($payload, $this->pathTraversalPatterns);
    }

    /**
     * Detect malicious user agent
     */
    protected function detectMaliciousUserAgent(string $userAgent): bool
    {
        $ua = strtolower($userAgent);
        foreach ($this->blockedUserAgents as $blocked) {
            if (str_contains($ua, strtolower($blocked))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check rate limiting
     */
    protected function checkRateLimit(string $ip): array
    {
        $key = "attack_detection:ratelimit:{$ip}";
        $blockKey = "attack_detection:blocked:{$ip}";

        // Check if IP is currently blocked
        $blockData = Cache::get($blockKey);
        if ($blockData) {
            return [
                'blocked' => true,
                'isDDoS' => $blockData['type'] === 'DDoS',
                'requestCount' => $blockData['count'] ?? 0,
            ];
        }

        // Get current request timestamps
        $timestamps = Cache::get($key, []);
        $now = time();
        $windowStart = $now - $this->rateLimitWindow;

        // Filter out old timestamps
        $timestamps = array_filter($timestamps, fn($ts) => $ts > $windowStart);
        $timestamps[] = $now;

        // Check for DDoS
        if (count($timestamps) > $this->ddosThreshold) {
            Cache::put($blockKey, ['type' => 'DDoS', 'count' => count($timestamps)], $this->blockTtl);
            Log::critical('[SECURITY] DDoS Attack Detected', [
                'ip' => $ip,
                'requests' => count($timestamps),
                'window' => $this->rateLimitWindow,
            ]);
            return ['blocked' => true, 'isDDoS' => true, 'requestCount' => count($timestamps)];
        }

        // Check for rate limit
        if (count($timestamps) > $this->rateLimitMax) {
            Cache::put($blockKey, ['type' => 'RATE_LIMIT', 'count' => count($timestamps)], 30);
            Log::warning('[SECURITY] Rate Limit Exceeded', [
                'ip' => $ip,
                'requests' => count($timestamps),
            ]);
            return ['blocked' => true, 'isDDoS' => false, 'requestCount' => count($timestamps)];
        }

        // Update timestamps
        Cache::put($key, $timestamps, $this->rateLimitWindow);

        return ['blocked' => false, 'isDDoS' => false, 'requestCount' => count($timestamps)];
    }

    /**
     * Log attack attempt
     */
    protected function logAttack(string $type, string $ip, string $url, string $payload, string $method): void
    {
        $data = [
            'type' => $type,
            'ip' => $ip,
            'url' => substr($url, 0, 500),
            'payload' => substr($payload, 0, 500),
            'method' => $method,
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::warning("[SECURITY] {$type} Attack Blocked", $data);

        // Store in database via audit service if available
        if (app()->bound(\App\Services\AuditService::class)) {
            try {
                app(\App\Services\AuditService::class)->log(
                    'security.attack_blocked',
                    null,
                    null,
                    $data
                );
            } catch (\Exception $e) {
                // Silently fail to avoid disrupting the response
            }
        }
    }

    /**
     * Get block page response
     */
    protected function getBlockPage(string $attackType, string $clientIP, string $url): Response
    {
        $pages = [
            'SQLi' => [
                'title' => '403 - SQL Injection Detected',
                'message' => 'Your request contains SQL injection patterns and has been blocked by our security system.',
                'icon' => '💉',
                'color' => '#e74c3c',
                'status' => 403,
            ],
            'XSS' => [
                'title' => '400 - Cross-Site Scripting Detected',
                'message' => 'Your request contains XSS (Cross-Site Scripting) patterns and has been blocked.',
                'icon' => '🔴',
                'color' => '#e74c3c',
                'status' => 400,
            ],
            'IDOR' => [
                'title' => '403 - Insecure Direct Object Reference',
                'message' => 'Unauthorized access attempt detected. Your request has been blocked.',
                'icon' => '🔓',
                'color' => '#f39c12',
                'status' => 403,
            ],
            'DDoS' => [
                'title' => '429 - DDoS Attack Detected',
                'message' => 'Suspicious activity detected from your IP. Access has been temporarily blocked.',
                'icon' => '⚡',
                'color' => '#c0392b',
                'status' => 429,
            ],
            'RATE_LIMIT' => [
                'title' => '429 - Rate Limit Exceeded',
                'message' => 'You have exceeded the request limit. Please try again later.',
                'icon' => '⏱️',
                'color' => '#e67e22',
                'status' => 429,
            ],
            'MALICIOUS_UA' => [
                'title' => '403 - Access Denied',
                'message' => 'Access from automated tools is not allowed. Please use a standard browser.',
                'icon' => '🤖',
                'color' => '#8e44ad',
                'status' => 403,
            ],
            'PATH_TRAVERSAL' => [
                'title' => '403 - Path Traversal Detected',
                'message' => 'File system access attempt detected and blocked.',
                'icon' => '📁',
                'color' => '#e74c3c',
                'status' => 403,
            ],
        ];

        $page = $pages[$attackType] ?? [
            'title' => '403 - Access Denied',
            'message' => 'Access denied due to suspicious activity.',
            'icon' => '🛡️',
            'color' => '#e74c3c',
            'status' => 403,
        ];

        $e = 'htmlspecialchars';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$e($page['title'])}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        h1 {
            color: {$e($page['color'])};
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .message {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .info-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 12px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; }
        .info-value { color: #94a3b8; font-family: monospace; }
        .back-btn {
            display: inline-block;
            padding: 10px 24px;
            background: rgba(255,255,255,0.1);
            color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.3);
        }
        .dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-bottom: 20px;
        }
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: {$e($page['color'])};
            animation: bounce 1.4s infinite;
        }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); opacity: 0.3; }
            40% { transform: translateY(-10px); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">{$page['icon']}</div>
        <div class="dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
        <h1>{$e($page['title'])}</h1>
        <p class="message">{$e($page['message'])}</p>
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">IP Address</span>
                <span class="info-value">{$e($clientIP)}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Attack Type</span>
                <span class="info-value" style="color:{$e($page['color'])}">{$e($attackType)}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Time</span>
                <span class="info-value">%s</span>
            </div>
        </div>
        <a href="/" class="back-btn">← Back to Home</a>
        <p style="color:#475569;font-size:11px;margin-top:16px;">Resort QR System - Security Protection</p>
    </div>
</body>
</html>
HTML;

        $html = sprintf($html, $e(now()->format('Y-m-d H:i:s')));

        return response($html, $page['status'])->header('Content-Type', 'text/html');
    }
}
