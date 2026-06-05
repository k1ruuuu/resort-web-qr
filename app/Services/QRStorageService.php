<?php

namespace App\Services;

use App\Models\GuestVoucher;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QRStorageService
{
    public function store(GuestVoucher $voucher): string
    {
        $payload = $voucher->secure_token;
        
        $options = new QROptions([
            'outputType' => 'png',
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 5,
        ]);
        
        $qr = new QRCode($options);
        $pngData = $qr->render($payload);
        
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
