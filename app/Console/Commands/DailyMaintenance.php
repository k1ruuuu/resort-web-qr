<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\VoucherStatus;
use App\Models\Booking;
use App\Models\DeliveryLog;
use App\Models\Guest;
use App\Models\GuestVoucher;
use App\Models\QrScanLog;
use App\Models\RedemptionLog;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\BookingService;
use App\Services\RedisCacheService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DailyMaintenance extends Command
{
    protected $signature = 'daily:maintenance
        {--auto-checkout : Permanently delete bookings past expected departure date and grace period}
        {--auto-cancel-no-show : Cancel expected arrival bookings past check_in date without check-in}
        {--expire-vouchers : Expire vouchers past their deadline}
        {--all : Run all maintenance tasks}';

    protected $description = 'Daily maintenance tasks: auto-checkout (hard delete), auto-cancel no-show, expire vouchers';

    public function __construct(
        private readonly AuditService $audit,
        private readonly RedisCacheService $cache,
        private readonly BookingService $bookings,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $all = $this->option('all');
        $exitCode = Command::SUCCESS;

        if ($all || $this->option('auto-checkout')) {
            if (!$this->runAutoCheckout()) {
                $exitCode = Command::FAILURE;
            }
        }

        if ($all || $this->option('auto-cancel-no-show')) {
            if (!$this->runAutoCancelNoShow()) {
                $exitCode = Command::FAILURE;
            }
        }

        if ($all || $this->option('expire-vouchers')) {
            if (!$this->runExpireVouchers()) {
                $exitCode = Command::FAILURE;
            }
        }

        if (!$all && !$this->option('auto-checkout') && !$this->option('auto-cancel-no-show') && !$this->option('expire-vouchers')) {
            $this->warn('No tasks specified. Use --all or one of: --auto-checkout, --auto-cancel-no-show, --expire-vouchers');
            $exitCode = Command::FAILURE;
        }

        return $exitCode;
    }

    private function runAutoCheckout(): bool
    {
        $this->info('Checking for bookings to auto-checkout and delete (past checkout cutoff + grace period)...');
        $cutoffTime = Setting::get('maintenance.checkout_cutoff', '12:35');
        $count = 0;

        Booking::query()
            ->whereIn('status', [BookingStatus::CheckIn, BookingStatus::ExpectedDeparture])
            ->where('check_out', '<=', Carbon::now('Asia/Jakarta')->toDateString())
            ->with(['guest', 'guestVoucher.facilityExchanges', 'room', 'property'])
            ->chunk(100, function ($bookings) use ($cutoffTime, &$count) {
                foreach ($bookings as $booking) {
                    $timezone = $booking->property?->timezone ?? 'Asia/Jakarta';
                    $localNow = Carbon::now($timezone);
                    $cutoff = Carbon::parse($booking->check_out, $timezone)
                        ->startOfDay()
                        ->setTimeFromTimeString($cutoffTime);

                    // Extended 1 hour past cutoff for one-time facility grace period (13:35 WIB)
                    $extendedCutoff = $cutoff->copy()->addHour();

                    if ($localNow->lt($extendedCutoff)) {
                        continue;
                    }

                    $voucherId = $booking->guestVoucher?->id;
                    $guestName = $booking->guest?->full_name ?? $booking->guestVoucher?->guest_name;
                    $roomName = $booking->room_label ?? $booking->room?->number ?? $booking->room?->label;
                    $bookingCode = $booking->booking_code ?? $booking->reference;
                    $propertyId = $booking->property_id;
                    $guestId = $booking->guest_id;
                    $guestDeleted = false;

                    // 1. Ensure snapshots on QrScanLog are filled before deleting
                    if ($voucherId) {
                        QrScanLog::query()
                            ->where('guest_voucher_id', $voucherId)
                            ->update([
                                'guest_name' => DB::raw("COALESCE(guest_name, " . ($guestName !== null ? DB::getPdo()->quote($guestName) : "NULL") . ")"),
                                'room_name' => DB::raw("COALESCE(room_name, " . ($roomName !== null ? DB::getPdo()->quote($roomName) : "NULL") . ")"),
                                'booking_code' => DB::raw("COALESCE(booking_code, " . ($bookingCode !== null ? DB::getPdo()->quote($bookingCode) : "NULL") . ")"),
                            ]);
                    }

                    // 2. Ensure snapshots on RedemptionLog are filled before deleting
                    RedemptionLog::query()
                        ->where(function ($q) use ($booking, $voucherId, $guestId) {
                            $q->where('booking_id', $booking->id);
                            if ($voucherId) {
                                $q->orWhere('guest_voucher_id', $voucherId);
                            }
                            if ($guestId) {
                                $q->orWhere('guest_id', $guestId);
                            }
                        })
                        ->update([
                            'guest_name' => DB::raw("COALESCE(guest_name, " . ($guestName !== null ? DB::getPdo()->quote($guestName) : "NULL") . ")"),
                            'room_name' => DB::raw("COALESCE(room_name, " . ($roomName !== null ? DB::getPdo()->quote($roomName) : "NULL") . ")"),
                            'booking_code' => DB::raw("COALESCE(booking_code, " . ($bookingCode !== null ? DB::getPdo()->quote($bookingCode) : "NULL") . ")"),
                            'property_id' => DB::raw("COALESCE(property_id, " . ($propertyId ? (int)$propertyId : "NULL") . ")"),
                        ]);

                    // 3. Ensure snapshots on DeliveryLog are filled before deleting
                    DeliveryLog::query()
                        ->where(function ($q) use ($booking, $voucherId, $guestId) {
                            $q->where('booking_id', $booking->id);
                            if ($voucherId) {
                                $q->orWhere('guest_voucher_id', $voucherId);
                            }
                            if ($guestId) {
                                $q->orWhere('guest_id', $guestId);
                            }
                        })
                        ->update([
                            'guest_name' => DB::raw("COALESCE(guest_name, " . ($guestName !== null ? DB::getPdo()->quote($guestName) : "NULL") . ")"),
                            'booking_code' => DB::raw("COALESCE(booking_code, " . ($bookingCode !== null ? DB::getPdo()->quote($bookingCode) : "NULL") . ")"),
                        ]);

                    $auditData = [
                        'booking_id' => $booking->id,
                        'booking_code' => $bookingCode,
                        'guest_id' => $guestId,
                        'guest_name' => $guestName,
                        'property_id' => $propertyId,
                        'status' => BookingStatus::ExpectedDeparture->value,
                        'check_in' => $booking->check_in?->toDateString(),
                        'check_out' => $booking->check_out?->toDateString(),
                        'checked_out_at' => $localNow->toDateTimeString(),
                    ];

                    DB::transaction(function () use ($booking, $guestId, &$guestDeleted, $localNow) {
                        // Delete voucher facility exchanges and voucher
                        if ($booking->guestVoucher) {
                            $booking->guestVoucher->facilityExchanges()->delete();
                            $this->cache->invalidateVoucher($booking->guestVoucher);
                            $booking->guestVoucher->delete();
                        }

                        // Delete booking facilities
                        $booking->bookingFacilities()->delete();

                        // Invalidate booking cache
                        $this->cache->invalidateBooking($booking);

                        // Set status to ExpectedDeparture (Checked Out) with timestamp before deletion
                        $booking->status = BookingStatus::ExpectedDeparture;
                        $booking->checked_out_at = $localNow;
                        $booking->save();

                        // Permanently delete booking
                        $booking->forceDelete();

                        // Delete guest if no other bookings remain
                        if ($guestId && !Booking::withTrashed()->where('guest_id', $guestId)->exists()) {
                            Guest::withTrashed()->where('id', $guestId)->forceDelete();
                            $guestDeleted = true;
                        }
                    });

                    $this->audit->log('booking.auto_checked_out_and_deleted', null, $auditData, null);

                    $count++;
                    $this->line("Auto-checked out and permanently deleted booking #{$booking->id} ({$bookingCode})" . ($guestDeleted ? " and guest #{$guestId}" : ""));
                }
            });

        if ($count > 0) {
            $this->info("Auto-checked out and deleted {$count} booking(s) past checkout cutoff.");
        } else {
            $this->info('No bookings to auto-checkout.');
        }

        return true;
    }

    private function runAutoCancelNoShow(): bool
    {
        $this->info('Checking for no-show bookings to cancel...');
        $noShowDays = (int) Setting::get('maintenance.no_show_days', '1');
        $count = 0;

        $cutoffDate = Carbon::now()->subDays($noShowDays)->toDateString();

        Booking::query()
            ->where('status', BookingStatus::ExpectedArrival)
            ->where('check_in', '<=', $cutoffDate)
            ->chunk(100, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $old = $booking->only(['status']);
                    $booking->update(['status' => BookingStatus::Cancelled]);

                    if ($booking->guestVoucher) {
                        $booking->guestVoucher->update(['status' => VoucherStatus::Cancelled]);
                        $this->cache->invalidateVoucher($booking->guestVoucher);
                    }

                    $this->audit->log('booking.auto_cancelled_no_show', $booking, $old, $booking->only(['status']));
                    $this->cache->invalidateBooking($booking);

                    $count++;
                    $this->line("Cancelled no-show booking #{$booking->id} ({$booking->reference})");
                }
            });

        if ($count > 0) {
            $this->info("Cancelled {$count} no-show booking(s).");
        } else {
            $this->info('No no-show bookings to cancel.');
        }

        return true;
    }

    private function runExpireVouchers(): bool
    {
        $this->info('Expiring vouchers past deadline...');
        $count = 0;

        GuestVoucher::query()
            ->where('status', VoucherStatus::Active)
            ->with(['booking.property', 'property'])
            ->chunk(100, function ($vouchers) use (&$count) {
                foreach ($vouchers as $voucher) {
                    if (!$this->shouldExpire($voucher)) {
                        continue;
                    }

                    $oldStatus = $voucher->status->value;
                    $voucher->update(['status' => VoucherStatus::Expired]);

                    $this->audit->log(
                        'voucher.auto_expired',
                        $voucher,
                        ['status' => $oldStatus],
                        ['status' => VoucherStatus::Expired->value]
                    );

                    $this->cache->invalidateVoucher($voucher);

                    $count++;
                    $identifier = $voucher->booking_id ? "booking #{$voucher->booking_id}" : "temp voucher #{$voucher->id}";
                    $this->line("Expired voucher #{$voucher->id} for {$identifier}");
                }
            });

        if ($count > 0) {
            $this->info("Expired {$count} voucher(s).");
        } else {
            $this->info('No vouchers to expire.');
        }

        return true;
    }

    private function shouldExpire(GuestVoucher $voucher): bool
    {
        if ($voucher->category === 'temporary') {
            $expiresAt = $voucher->expires_at;
            if (!$expiresAt) {
                return false;
            }

            $timezone = $voucher->property?->timezone ?? 'UTC';

            return Carbon::now($timezone)->gte($expiresAt);
        }

        if (!$voucher->booking) {
            return false;
        }

        $timezone = $voucher->booking->property->timezone ?? 'UTC';
        $currentDateTime = Carbon::now($timezone);
        $cutoffTime = Setting::get('maintenance.checkout_cutoff', '12:35');

        $checkOutDate = Carbon::parse($voucher->booking->check_out)
            ->setTimezone($timezone)
            ->startOfDay()
            ->setTimeFromTimeString($cutoffTime);

        // Extended 1 hour past checkout cutoff for one-time facilities
        if ($voucher->isOneTimeGracePeriodActive($currentDateTime)) {
            return false;
        }

        return $currentDateTime->gte($checkOutDate);
    }
}
