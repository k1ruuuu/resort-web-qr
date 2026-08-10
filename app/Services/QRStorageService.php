<?php

namespace App\Services;

use App\Models\GuestVoucher;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QRStorageService
{
    public function __construct(private readonly QrCodeService $qr) {}

    public function store(GuestVoucher $voucher): string
    {
        $pngData = $this->qr->templateImageBytes($voucher->secure_token);

        $filename = "qrcodes/qr-{$voucher->secure_token}.png";
        
        Storage::disk('public')->put($filename, $pngData);
        
        $absolutePath = Storage::disk('public')->path($filename);
        
        Log::info("QR Generated", [
            'file_path' => $absolutePath,
            'voucher_id' => $voucher->id
        ]);
        
        return $filename;
    }

    public function exists(string $filename): bool
    {
        return Storage::disk('public')->exists($filename);
    }

    public function getAbsolutePath(string $filename): string
    {
        return Storage::disk('public')->path($filename);
    }
}
