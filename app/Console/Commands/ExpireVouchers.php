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

    protected $description = 'Automatically expire vouchers that have passed their checkout time (9 PM)';

    public function __construct(private readonly AuditService $audit)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking for vouchers to expire...');

        $vouchers = GuestVoucher::query()
            ->where('status', VoucherStatus::Active)
            ->with(['booking.property'])
            ->get();

        $expiredCount = 0;

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
                $this->line("Expired voucher #{$voucher->id} for booking #{$voucher->booking_id}");
            }
        }

        if ($expiredCount > 0) {
            $this->info("Successfully expired {$expiredCount} voucher(s).");
        } else {
            $this->info('No vouchers to expire.');
        }

        return Command::SUCCESS;
    }

    private function shouldExpire(GuestVoucher $voucher): bool
    {
        $timezone = $voucher->booking->property->timezone ?? 'UTC';
        $currentDateTime = Carbon::now($timezone);
        
        $checkOutDate = Carbon::parse($voucher->booking->check_out)
            ->setTimezone($timezone)
            ->startOfDay()
            ->setTime(21, 0, 0); // 9 PM on checkout date

        return $currentDateTime->gte($checkOutDate);
    }
}
