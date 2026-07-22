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
            abort(500, 'Failed to generate QR code image.');
        }

        $templatePath = public_path('Barcode-Chanaya.png');

        if (!file_exists($templatePath)) {
            imagedestroy($qrImage);
            abort(404, 'Template Barcode Chanaya.png tidak ditemukan di folder public.');
        }

        $templateImage = imagecreatefrompng($templatePath);

        if ($templateImage === false) {
            imagedestroy($qrImage);
            abort(500, 'Failed to load template image.');
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
            abort(500, 'QR code is too large for the template.');
        }

        imagecopy($templateImage, $qrImage, $posX, $posY, 0, 0, $qrWidth, $qrHeight);

        ob_start();
        imagejpeg($templateImage, null, 90);
        $finalImage = ob_get_clean();

        imagedestroy($templateImage);
        imagedestroy($qrImage);

        return response($finalImage, 200, [
            'Content-Type'  => 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
            'Content-Length' => strlen($finalImage),
        ]);
    }

    public function payloadForVoucher(GuestVoucher $voucher): string
    {
        return $voucher->secure_token;
    }

    public function publicPageUrl(GuestVoucher $voucher): string
    {
        return route('vouchers.public', ['token' => $voucher->secure_token]);
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