<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\VoucherException;
use App\Models\Booking;
use App\Models\GuestVoucher;
use App\Models\Outlet;
use App\Models\QrScanLog;
use App\Models\RedemptionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly BookingService $bookings,
        private readonly StayQuotaService $quota,
    ) {}

    public function generateForBooking(Booking $booking): GuestVoucher
    {
        if ($booking->status !== BookingStatus::CheckedIn) {
            throw VoucherException::bookingNotCheckedIn();
        }

        $booking->loadMissing(['property', 'room.roomType', 'bookingFacilities.facilityTemplate', 'guest']);

        if ($booking->bookingFacilities->isEmpty()) {
            $this->bookings->syncDefaultFacilities($booking);
            $booking->load('bookingFacilities.facilityTemplate');
        }

        if ($booking->bookingFacilities->isEmpty()) {
            throw VoucherException::noFacilities();
        }

        $guestName = $booking->guest->full_name;
        $roomCode = $booking->room?->code ?? $booking->room?->number ?? 'ROOM';
        $roomName = $booking->room?->label ?? $booking->room?->roomType?->name ?? 'Room';
        $date = $booking->check_in->format('Y-m-d');

        $guestNameClean = preg_replace('/[^a-zA-Z0-9]/', '', $guestName);
        $roomCodeClean = preg_replace('/[^a-zA-Z0-9]/', '', $roomCode);
        $roomNameClean = preg_replace('/[^a-zA-Z0-9]/', '', $roomName);

        $baseQrCode = "{$guestNameClean}+{$roomCodeClean}+{$roomNameClean}+{$date}";

        $qrCode = $baseQrCode;
        $counter = 1;
        while (GuestVoucher::query()->where('qr_code', $qrCode)->exists()) {
            $qrCode = "{$baseQrCode}-{$counter}";
            $counter++;
        }

        $secureToken = (string) Str::random(32);

        return DB::transaction(function () use ($booking, $qrCode, $secureToken) {
            $voucher = GuestVoucher::query()->where('booking_id', $booking->id)->first();

            if (!$voucher) {
                $voucher = GuestVoucher::query()->create([
                    'booking_id' => $booking->id,
                    'guest_id' => $booking->guest_id,
                    'qr_code' => $qrCode,
                    'secure_token' => $secureToken,
                    'status' => VoucherStatus::Active,
                    'generated_at' => now(),
                ]);

                $this->audit->log('voucher.generated', $voucher, null, $voucher->toArray());
            }

            return $voucher;
        });
    }

    public function redeem(
        string $qrCode,
        Outlet $outlet,
        User $user,
        int $facilityTemplateId,
        int $paxUsed = 1
    ): RedemptionLog {
        try {
            return DB::transaction(function () use ($qrCode, $outlet, $user, $facilityTemplateId, $paxUsed) {
                $voucher = GuestVoucher::query()
                    ->where('secure_token', $qrCode)
                    ->orWhere('qr_code', $qrCode)
                    ->lockForUpdate()
                    ->first();

                if (!$voucher) {
                    throw VoucherException::notFound();
                }

                if ($voucher->status !== VoucherStatus::Active) {
                    throw new VoucherException('Voucher is no longer active.', 422);
                }

                if ($voucher->booking->status !== BookingStatus::CheckedIn) {
                    throw new VoucherException('Booking is not currently checked in.', 422);
                }

                $timezone = $voucher->booking->property->timezone ?? 'UTC';
                $currentDate = Carbon::today($timezone);
                $checkInDate = Carbon::parse($voucher->booking->check_in)->setTimezone($timezone)->startOfDay();
                $checkOutDate = Carbon::parse($voucher->booking->check_out)->setTimezone($timezone)->startOfDay();

                // Voucher is valid from check-in date up to and including checkout date
                if ($currentDate->lt($checkInDate) || $currentDate->gt($checkOutDate)) {
                    throw new VoucherException('QR code is only valid during the check-in period.', 422);
                }

                if ($outlet->property_id !== $voucher->booking->property_id) {
                    throw new VoucherException('This outlet belongs to a different property.', 403);
                }

                $today = Carbon::today($voucher->booking->property->timezone ?? 'UTC');
                $statuses = $voucher->getFacilityStatuses($today);
                $facilityStatus = $statuses->firstWhere('facility_template_id', $facilityTemplateId);

                if (!$facilityStatus) {
                    throw new VoucherException('Facility is not linked to this booking.', 422);
                }

                if (!$facilityStatus->is_available) {
                    throw VoucherException::expired();
                }

                if ($paxUsed > $facilityStatus->quota_remaining) {
                    throw VoucherException::quotaExceeded();
                }

                $now = now();
                $remainingQuota = $facilityStatus->quota_remaining - $paxUsed;

                $log = RedemptionLog::query()->create([
                    'guest_voucher_id' => $voucher->id,
                    'guest_id' => $voucher->guest_id,
                    'booking_id' => $voucher->booking_id,
                    'facility_template_id' => $facilityTemplateId,
                    'outlet_id' => $outlet->id,
                    'user_id' => $user->id,
                    'pax_used' => $paxUsed,
                    'remaining_quota' => $remainingQuota,
                    'date' => $today->toDateString(),
                    'time' => $now->toTimeString(),
                    'ip_address' => request()->ip(),
                ]);

                $this->logScan($qrCode, $voucher, $outlet, $user, 'success');
                $this->audit->log('voucher.redeemed', $voucher, null, $log->toArray());

                // Check if all facilities are fully redeemed
                $this->updateVoucherStatusIfFullyRedeemed($voucher);

                return $log->load(['guestVoucher', 'guest', 'booking', 'facilityTemplate', 'outlet', 'user']);
            });
        } catch (VoucherException $e) {
            // Log the failed scan attempt outside the transaction
            $voucher = GuestVoucher::query()
                ->where('secure_token', $qrCode)
                ->orWhere('qr_code', $qrCode)
                ->first();

            $result = $this->mapExceptionToScanResult($e, $voucher);
            $this->logScan($qrCode, $voucher, $outlet, $user, $result);

            throw $e;
        } catch (\Exception $e) {
            // Log unexpected errors
            $voucher = GuestVoucher::query()
                ->where('secure_token', $qrCode)
                ->orWhere('qr_code', $qrCode)
                ->first();

            $this->logScan($qrCode, $voucher, $outlet, $user, 'system_error');

            throw $e;
        }
    }

    private function mapExceptionToScanResult(\Exception $e, ?GuestVoucher $voucher): string
    {
        if (!$voucher) {
            return 'not_found';
        }

        return match ($e->getMessage()) {
            'Voucher not found.' => 'not_found',
            'Voucher is no longer active.' => 'voucher_not_active',
            'Booking is not currently checked in.' => 'booking_not_checked_in',
            'QR code is only valid during the check-in period.' => 'outside_stay_period',
            'This outlet belongs to a different property.' => 'invalid_outlet',
            'Facility is not linked to this booking.' => 'facility_not_linked',
            'Voucher is expired or not valid today.' => 'invalid_date',
            'Voucher quota exceeded.' => 'quota_exceeded',
            default => 'validation_error',
        };
    }

    private function updateVoucherStatusIfFullyRedeemed(GuestVoucher $voucher): void
    {
        $timezone = $voucher->booking->property->timezone ?? 'UTC';
        $today = Carbon::today($timezone);
        $statuses = $voucher->getFacilityStatuses($today);

        // Check if all available facilities for today are fully redeemed
        $allFullyRedeemed = $statuses
            ->filter(fn($status) => $status->is_available)
            ->every(fn($status) => $status->quota_remaining === 0);

        if ($allFullyRedeemed && $statuses->where('is_available', true)->isNotEmpty()) {
            $voucher->update(['status' => VoucherStatus::Redeemed]);
            $this->audit->log('voucher.status_changed', $voucher, ['status' => VoucherStatus::Active->value], ['status' => VoucherStatus::Redeemed->value]);
        }
    }

    private function logScan(
        string $qrCode,
        ?GuestVoucher $voucher,
        Outlet $outlet,
        User $user,
        string $result,
    ): void {
        QrScanLog::query()->create([
            'qr_code' => $qrCode,
            'secure_token' => $voucher?->secure_token,
            'guest_voucher_id' => $voucher?->id,
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'scan_result' => $result,
            'scanned_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
