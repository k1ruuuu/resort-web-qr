<?php

namespace App\Services;

use App\Models\GuestVoucher;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Response;

class QrCodeService
{
    public function svg(string $payload, int $size = 220): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'drawLightModules' => true,
            'scale' => max(4, (int) ceil($size / 25)),
        ]);

        $svg = (new QRCode($options))->render($payload);

        $svg = preg_replace('/<\?xml[^>]*\?>\s*/', '', $svg) ?? $svg;

        if (! preg_match('/\bwidth="/', $svg)) {
            $svg = preg_replace(
                '/<svg\b/',
                '<svg width="'.$size.'" height="'.$size.'" style="display:block"',
                $svg,
                1
            ) ?? $svg;
        }

        return $svg;
    }

    public function svgResponse(string $payload, int $size = 220): Response
    {
        return response($this->svg($payload, $size), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function templateResponse(string $payload): Response
    {
        $merged = $this->renderMergedTemplate($payload);

        if ($merged === null) {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'outputBase64' => true,
                'eccLevel' => QRCode::ECC_L,
                'scale' => 12,
                'margin' => 2,
            ]);
            $fallbackData = (new QRCode($options))->render($payload);

            return response(
                '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>QR Code</title>'
                . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
                . '<body style="display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#f5f5f5;">'
                . '<img src="' . $fallbackData . '" alt="QR Code" style="max-width:90vw;max-height:90vh;"></body></html>',
                200,
                ['Content-Type' => 'text/html', 'Cache-Control' => 'public, max-age=3600']
            );
        }

        ob_start();
        imagejpeg($merged, null, 90);
        $finalImage = ob_get_clean();
        imagedestroy($merged);

        return response($finalImage, 200, [
            'Content-Type'  => 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
            'Content-Length' => strlen($finalImage),
        ]);
    }

    public function templateImageBytes(string $payload): string
    {
        $merged = $this->renderMergedTemplate($payload);

        if ($merged === null) {
            $options = new QROptions([
                'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
                'outputBase64' => false,
                'eccLevel'     => QRCode::ECC_L,
                'scale'        => 12,
                'margin'       => 2,
            ]);
            return (new QRCode($options))->render($payload);
        }

        ob_start();
        imagepng($merged, null, 9);
        $pngData = ob_get_clean();
        imagedestroy($merged);

        return $pngData;
    }

    private function renderMergedTemplate(string $payload): ?\GdImage
    {
        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'outputBase64' => false,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 12,
            'margin'       => 2,
        ]);

        $qrRawData = (new QRCode($options))->render($payload);
        $qrImage = imagecreatefromstring($qrRawData);

        if ($qrImage === false) {
            return null;
        }

        $templateImage = $this->loadTemplateImage();
        if ($templateImage === null) {
            imagedestroy($qrImage);
            return null;
        }

        $templateWidth = imagesx($templateImage);
        $templateHeight = imagesy($templateImage);
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);

        $posX = (int) (($templateWidth - $qrWidth) / 2);
        $posY = (int) ($templateHeight * 0.48);

        if ($posX < 0 || $posY < 0) {
            imagedestroy($templateImage);
            imagedestroy($qrImage);
            return null;
        }

        imagecopy($templateImage, $qrImage, $posX, $posY, 0, 0, $qrWidth, $qrHeight);
        imagedestroy($qrImage);

        return $templateImage;
    }

    private function loadTemplateImage(): \GdImage|null
    {
        $templatePaths = [
            public_path('img/Barcode-Chanaya.jpg'),
            public_path('img/Barcode-Chanaya.png'),
            public_path('Barcode-Chanaya.png'),
            public_path('Barcode-Chanaya.jpg'),
        ];

        foreach ($templatePaths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            $lower = strtolower($path);
            $templateImage = str_ends_with($lower, '.jpg') || str_ends_with($lower, '.jpeg')
                ? @imagecreatefromjpeg($path)
                : @imagecreatefrompng($path);

            if ($templateImage !== false) {
                return $templateImage;
            }
        }

        return null;
    }

    public function payloadForVoucher(GuestVoucher $voucher): string
    {
        return $voucher->secure_token;
    }

    public function imageUrl(GuestVoucher $voucher): string
    {
        return route('vouchers.public.qr', ['token' => $voucher->secure_token]);
    }

    public function adminImageUrl(GuestVoucher $voucher): string
    {
        return route('vouchers.qr', $voucher);
    }
}