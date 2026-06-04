<?php

namespace App\Services;

use App\Models\DailyVoucher;
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

    /** QR image encodes client payload: Room+Facility+Date */
    public function payloadForVoucher(DailyVoucher $voucher): string
    {
        return $voucher->qr_code ?? $voucher->qr_token ?? '';
    }

    public function publicPageUrl(DailyVoucher $voucher): string
    {
        return route('vouchers.public', ['token' => $voucher->public_token]);
    }

    public function imageUrl(DailyVoucher $voucher): string
    {
        return route('vouchers.public.qr', ['token' => $voucher->public_token]);
    }

    public function adminImageUrl(DailyVoucher $voucher): string
    {
        return route('vouchers.qr', $voucher);
    }
}
