<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PublicUrlGeneratorService
{
    public function generate(string $filename): string
    {
        $appUrl = config('app.url');
        
        $appUrl = rtrim($appUrl, '/');
        
        $url = "{$appUrl}/storage/{$filename}";
        
        $this->validateUrl($url);
        
        Log::info("QR Public URL Generated", [
            'generated_url' => $url
        ]);
        
        return $url;
    }

    public function validateUrl(string $url): void
    {
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            throw new \InvalidArgumentException("Invalid URL format: {$url}");
        }
        
        $scheme = strtolower($parsed['scheme']);
        $host = strtolower($parsed['host']);
        
        if ($scheme !== 'https') {
            throw new \InvalidArgumentException("URL scheme must be HTTPS, got: '{$scheme}'");
        }
        
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            throw new \InvalidArgumentException("URL host cannot be local (localhost/127.0.0.1), got: '{$host}'");
        }
        
        if (str_contains($url, '\\') || str_contains($url, 'storage/app/public') || str_contains($url, 'C:')) {
            throw new \InvalidArgumentException("URL cannot be a local filesystem path, got: '{$url}'");
        }
    }
}
