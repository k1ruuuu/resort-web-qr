<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\VoucherStatus;
use App\Models\Booking;
use App\Models\DeliveryLog;
use App\Models\GuestVoucher;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\BookingService;
use App\Services\RedisCacheService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DailyMaintenance extends Command
{
    protected $signature = 'daily:maintenance
        {--auto-checkout : Delete bookings past their expected departure date}
        {--auto-cancel-no-show : Cancel expected arrival bookings past check_in date without check-in}
        {--expire-vouchers : Expire vouchers past their deadline}
        {--all : Run all maintenance tasks}';

    protected $description = 'Daily maintenance tasks: auto-checkout (delete), auto-cancel no-show, expire vouchers';

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
            if (!$this->runAutoDelete()) {
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

    private function runAutoDelete(): bool
    {
        $this->info('Checking for bookings to check out (past expected departure)...');
        $cutoffTime = Setting::get('maintenance.checkout_cutoff', '12:30');
        $count = 0;

        Booking::query()
            ->where('status', BookingStatus::CheckIn)
            ->where('check_out', '<=', Carbon::now()->toDateString())
            ->chunk(100, function ($bookings) use ($cutoffTime, &$count) {
                foreach ($bookings as $booking) {
                    $timezone = $booking->property?->timezone ?? 'UTC';
                    $localNow = Carbon::now($timezone);
                    $cutoff = Carbon::parse($booking->check_out)
                        ->setTimezone($timezone)
                        ->startOfDay()
                        ->setTimeFromTimeString($cutoffTime);

                    if ($localNow->lt($cutoff)) {
                        continue;
                    }

                    $ref = $booking->reference;
                    $this->bookings->checkOut($booking);
                    $count++;
                    $this->line("Checked out booking #{$booking->id} ({$ref})");
                }
            });

        if ($count > 0) {
            $this->info("Checked out {$count} booking(s) past expected departure.");
        } else {
            $this->info('No bookings to check out.');
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
        $cutoffTime = Setting::get('maintenance.checkout_cutoff', '12:30');

        $checkOutDate = Carbon::parse($voucher->booking->check_out)
            ->setTimezone($timezone)
            ->startOfDay()
            ->setTimeFromTimeString($cutoffTime);

        return $currentDateTime->gte($checkOutDate);
    }
}
