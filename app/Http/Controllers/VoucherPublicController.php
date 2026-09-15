<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\VoucherStatus;
use App\Models\GuestVoucher;
use App\Services\QrCodeService;
use App\Services\VoucherService;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\View\View;

class VoucherPublicController extends Controller
{
    public function __construct(
        private readonly VoucherService $vouchers,
        private readonly QrCodeService $qr,
    ) {}

    public function publicShow(string $token): View
    {
        $voucher = $this->findByPublicToken($token);
        $voucher->load(['booking.guest', 'booking.room', 'property']);

        $this->vouchers->checkAndExpireIfNeeded($voucher);
        $voucher->refresh();

        $today = Carbon::today($voucher->property?->timezone ?? $voucher->booking?->property?->timezone ?? 'UTC');
        $facilityStatuses = $voucher->getFacilityStatuses($today);

        $timezone = $voucher->property?->timezone ?? $voucher->booking?->property?->timezone ?? 'UTC';
        $now = Carbon::now($timezone);

        $isOneTimeGrace = $voucher->isOneTimeGracePeriodActive($now);

        if ($voucher->status !== VoucherStatus::Active && !$isOneTimeGrace) {
            $voucherState = 'inactive';
        } elseif ($voucher->category === 'temporary' && $voucher->expires_at && $now->gte($voucher->expires_at)) {
            $voucherState = 'expired';
        } elseif ($voucher->booking && $voucher->booking->status !== BookingStatus::CheckIn && !$isOneTimeGrace) {
            $voucherState = 'not_checked_in';
        } else {
            $voucherState = 'active';
        }

        return view('vouchers.public', [
            'voucher' => $voucher,
            'qrImageUrl' => $this->qr->imageUrl($voucher),
            'facilityStatuses' => $facilityStatuses,
            'voucherState' => $voucherState,
        ]);
    }

    public function qrImagePublic(string $token): Response
    {
        $voucher = $this->findByPublicToken($token);

        // L-15: never serve QR images for vouchers that are no longer usable
        $timezone = $voucher->property?->timezone ?? $voucher->booking?->property?->timezone ?? 'UTC';
        $now = Carbon::now($timezone);

        // Repair legacy 'redeemed' vouchers (quota used up on a past day) so the
        // QR image stays alive for the rest of the stay
        $this->vouchers->checkAndExpireIfNeeded($voucher);
        $voucher->refresh();

        $isOneTimeGrace = $voucher->isOneTimeGracePeriodActive($now);

        if ($voucher->status !== VoucherStatus::Active && !$isOneTimeGrace) {
            abort(410, 'Voucher is no longer active.');
        }

        if ($voucher->category === 'temporary' && $voucher->expires_at && $now->gte($voucher->expires_at)) {
            abort(410, 'Voucher has expired.');
        }

        // Public guest page: plain QR only (no branded template), sized for display
        return $this->qr->svgResponse($this->qr->payloadForVoucher($voucher), 800);
    }

    private function findByPublicToken(string $token): GuestVoucher
    {
        return GuestVoucher::query()
            ->where('secure_token', $token)
            ->firstOrFail();
    }
}
