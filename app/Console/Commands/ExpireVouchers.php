<?php

namespace App\Console\Commands;

use App\Enums\VoucherStatus;
use App\Models\GuestVoucher;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireVouchers extends Command
{
    protected $signature = 'voucher:expire';

    protected $description = 'Automatically expire vouchers that have passed their deadline (9 PM checkout or expires_at)';

    public function __construct(private readonly AuditService $audit)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking for vouchers to expire...');

        $expiredCount = 0;

        GuestVoucher::query()
            ->where('status', VoucherStatus::Active)
            ->with(['booking.property', 'property'])
            ->chunk(100, function ($vouchers) use (&$expiredCount) {
                foreach ($vouchers as $voucher) {
                    if ($this->shouldExpire($voucher)) {
                        $oldStatus = $voucher->status->value;
                        $voucher->update(['status' => VoucherStatus::Expired]);
                        
                        $this->audit->log(
                            'voucher.auto_expired',
                            $voucher,
                            ['status' => $oldStatus],
                            ['status' => VoucherStatus::Expired->value]
                        );

                        $expiredCount++;
                        $identifier = $voucher->booking_id ? "booking #{$voucher->booking_id}" : "temp voucher #{$voucher->id}";
                        $this->line("Expired voucher #{$voucher->id} for {$identifier}");
                    }
                }
            });

        if ($expiredCount > 0) {
            $this->info("Successfully expired {$expiredCount} voucher(s).");
        } else {
            $this->info('No vouchers to expire.');
        }

        return Command::SUCCESS;
    }

    private function shouldExpire(GuestVoucher $voucher): bool
    {
        if ($voucher->category === 'temporary') {
            $expiresAt = $voucher->expires_at;
            if (!$expiresAt) {
                return false;
            }

            $timezone = $voucher->property?->timezone ?? 'UTC';
            $currentDateTime = Carbon::now($timezone);

            return $currentDateTime->gte($expiresAt);
        }

        if (!$voucher->booking) {
            return false;
        }

        $timezone = $voucher->booking->property->timezone ?? 'UTC';
        $currentDateTime = Carbon::now($timezone);
        
        $checkOutDate = Carbon::parse($voucher->booking->check_out)
            ->setTimezone($timezone)
            ->startOfDay()
            ->setTime(21, 0, 0); // 9 PM on checkout date

        return $currentDateTime->gte($checkOutDate);
    }
}
