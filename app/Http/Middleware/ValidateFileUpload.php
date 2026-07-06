<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateFileUpload
{
    /**
     * Allowed MIME types
     */
    protected array $allowedMimes = [
        'text/csv',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/csv',
    ];

    /**
     * Dangerous file extensions to block
     */
    protected array $dangerousExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'exe', 'dll', 'bat', 'cmd', 'sh', 'bash',
        'js', 'jar', 'app', 'dmg', 'com', 'bin',
        'scr', 'msi', 'vbs', 'ps1', 'reg',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Check file is valid
            if (!$file->isValid()) {
                return back()->withErrors(['file' => 'File upload failed. Please try again.'])->withInput();
            }

            // Check file size (max 5MB for security)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file->getSize() > $maxSize) {
                return back()->withErrors(['file' => 'File is too large. Maximum size is 5MB.'])->withInput();
            }

            // Get file extension
            $extension = strtolower($file->getClientOriginalExtension());

            // Block dangerous extensions
            if (in_array($extension, $this->dangerousExtensions)) {
                \Log::warning('Dangerous file upload attempt blocked', [
                    'extension' => $extension,
                    'filename' => $file->getClientOriginalName(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()?->id,
                ]);

                return back()->withErrors(['file' => 'This file type is not allowed.'])->withInput();
            }

            // Verify MIME type
            $mimeType = $file->getMimeType();
            if (!in_array($mimeType, $this->allowedMimes)) {
                \Log::warning('Invalid MIME type upload attempt', [
                    'mime' => $mimeType,
                    'filename' => $file->getClientOriginalName(),
                    'ip' => $request->ip(),
                ]);

                return back()->withErrors(['file' => 'Invalid file type. Only CSV and Excel files are allowed.'])->withInput();
            }

            // Additional security: Read first bytes to verify actual file type
            $fileContent = file_get_contents($file->getRealPath(), false, null, 0, 1024);
            
            // Check for PHP tags (code injection attempt)
            if (stripos($fileContent, '<?php') !== false || stripos($fileContent, '<?=') !== false) {
                \Log::critical('Code injection attempt detected in file upload', [
                    'filename' => $file->getClientOriginalName(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()?->id,
                ]);

                return back()->withErrors(['file' => 'Malicious content detected in file.'])->withInput();
            }

            // Check for script tags (XSS attempt)
            if (stripos($fileContent, '<script') !== false) {
                \Log::warning('Script tag detected in file upload', [
                    'filename' => $file->getClientOriginalName(),
                    'ip' => $request->ip(),
                ]);

                return back()->withErrors(['file' => 'Invalid file content detected.'])->withInput();
            }

            // Log successful upload for audit
            \Log::info('File upload validated', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $mimeType,
                'user_id' => $request->user()?->id,
            ]);
        }

        return $next($request);
    }
}
