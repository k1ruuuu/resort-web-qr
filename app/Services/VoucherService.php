<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\VoucherException;
use App\Models\Booking;
use App\Models\BookingFacility;
use App\Models\DailyVoucher;
use App\Models\Outlet;
use App\Models\QrScanLog;
use App\Models\User;
use App\Models\VoucherUsageLog;
use App\Support\QrCodePayload;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class VoucherService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly BookingService $bookings,
        private readonly StayQuotaService $quota,
    ) {}

    /**
     * @return Collection<int, DailyVoucher>
     */
    public function generateForBooking(Booking $booking, ?Carbon $forDate = null): Collection
    {
        if ($booking->status !== BookingStatus::CheckedIn) {
            throw VoucherException::bookingNotCheckedIn();
        }

        $booking->loadMissing(['property', 'room.roomType', 'bookingFacilities.facilityTemplate']);

        if ($booking->bookingFacilities->isEmpty()) {
            $this->bookings->syncDefaultFacilities($booking);
            $booking->load('bookingFacilities.facilityTemplate');
        }

        if ($booking->bookingFacilities->isEmpty()) {
            throw VoucherException::noFacilities();
        }

        $timezone = $booking->property->timezone ?? 'UTC';
        $forDate ??= Carbon::today($timezone);
        $created = collect();

        DB::transaction(function () use ($booking, $forDate, &$created) {
            foreach ($booking->bookingFacilities as $facility) {
                if (! $this->dateWithinFacility($forDate, $facility, $booking->property->timezone ?? 'UTC')) {
                    continue;
                }

                $facility->loadMissing('facilityTemplate');
                $qrCode = QrCodePayload::build($booking, $facility->facilityTemplate, $forDate);
                $quotaTotal = $facility->quota_total ?: $this->quota->quotaForBooking($booking);

                $voucher = DailyVoucher::query()->firstOrCreate(
                    [
                        'booking_facility_id' => $facility->id,
                        'valid_date' => $forDate->toDateString(),
                    ],
                    [
                        'qr_code' => $qrCode,
                        'qr_token' => (string) Uuid::uuid4(),
                        'public_token' => (string) Uuid::uuid4(),
                        'booking_id' => $booking->id,
                        'facility_template_id' => $facility->facility_template_id,
                        'quota_total' => $quotaTotal,
                        'quota_remaining' => $quotaTotal,
                        'status' => VoucherStatus::Active,
                        'generated_at' => now(),
                    ]
                );

                if ($voucher->qr_code !== $qrCode) {
                    $voucher->update([
                        'qr_code' => $qrCode,
                        'public_token' => $voucher->public_token ?? (string) Uuid::uuid4(),
                    ]);
                }

                $created->push($voucher);
                $this->audit->log('voucher.generated', $voucher, null, $voucher->toArray());
            }
        });

        if ($created->isEmpty()) {
            throw VoucherException::noEligibleFacilitiesForDate($forDate->toDateString());
        }

        return $created;
    }

    public function redeem(
        string $qrCode,
        Outlet $outlet,
        User $user,
        int $paxUsed = 1,
    ): DailyVoucher {
        return DB::transaction(function () use ($qrCode, $outlet, $user, $paxUsed) {
            $voucher = DailyVoucher::query()
                ->where('qr_code', $qrCode)
                ->orWhere('qr_token', $qrCode)
                ->lockForUpdate()
                ->first();

            if (! $voucher) {
                $this->logScan($qrCode, null, $outlet, $user, 'not_found');

                throw VoucherException::notFound();
            }

            if ($outlet->facility_template_id !== $voucher->facility_template_id) {
                $this->logScan($qrCode, $voucher, $outlet, $user, 'invalid_outlet');

                throw VoucherException::invalidOutlet();
            }

            $today = Carbon::today($voucher->booking->property->timezone);

            if (! $voucher->valid_date->isSameDay($today)) {
                $this->logScan($qrCode, $voucher, $outlet, $user, 'invalid_date');

                throw VoucherException::expired();
            }

            if ($voucher->status === VoucherStatus::Redeemed) {
                $this->logScan($qrCode, $voucher, $outlet, $user, 'already_redeemed');

                throw VoucherException::alreadyRedeemed();
            }

            if ($paxUsed > $voucher->quota_remaining) {
                $this->logScan($qrCode, $voucher, $outlet, $user, 'quota_exceeded');

                throw VoucherException::quotaExceeded();
            }

            $now = now();
            $old = $voucher->only(['status', 'quota_remaining', 'redeemed_at']);

            $voucher->quota_remaining -= $paxUsed;
            $fullyRedeemed = $voucher->quota_remaining <= 0;

            $voucher->fill([
                'status' => $fullyRedeemed ? VoucherStatus::Redeemed : VoucherStatus::Active,
                'redeemed_at' => $fullyRedeemed ? $now : $voucher->redeemed_at,
                'redeemed_by_user_id' => $fullyRedeemed ? $user->id : $voucher->redeemed_by_user_id,
                'redeemed_at_outlet_id' => $fullyRedeemed ? $outlet->id : $voucher->redeemed_at_outlet_id,
            ])->save();

            VoucherUsageLog::query()->create([
                'daily_voucher_id' => $voucher->id,
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'action' => $fullyRedeemed ? 'redeemed' : 'partial_redeem',
                'pax_used' => $paxUsed,
                'used_at' => $now,
                'metadata' => [
                    'quota_remaining' => $voucher->quota_remaining,
                    'recorded_at' => $now->toIso8601String(),
                ],
            ]);

            $this->logScan($qrCode, $voucher, $outlet, $user, 'success');
            $this->audit->log('voucher.redeemed', $voucher, $old, $voucher->fresh()->toArray());

            return $voucher->fresh(['booking.guest', 'facilityTemplate']);
        });
    }

    private function dateWithinFacility(Carbon $date, BookingFacility $facility, string $timezone): bool
    {
        $day = $date->copy()->timezone($timezone)->toDateString();
        $start = $facility->start_date->format('Y-m-d');
        $end = $facility->end_date->format('Y-m-d');

        return $day >= $start && $day <= $end;
    }

    private function logScan(
        string $qrCode,
        ?DailyVoucher $voucher,
        Outlet $outlet,
        User $user,
        string $result,
    ): void {
        $qrToken = null;
        if ($voucher) {
            $qrToken = $voucher->qr_token;
        } elseif (Uuid::isValid($qrCode)) {
            $qrToken = $qrCode;
        }

        QrScanLog::query()->create([
            'qr_code' => $qrCode,
            'qr_token' => $qrToken,
            'daily_voucher_id' => $voucher?->id,
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'scan_result' => $result,
            'scanned_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
