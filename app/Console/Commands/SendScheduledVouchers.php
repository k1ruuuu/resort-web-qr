<?php

namespace App\Console\Commands;

use App\Services\VoucherDeliveryService;
use Illuminate\Console\Command;

class SendScheduledVouchers extends Command
{
    protected $signature = 'voucher:send-scheduled';

    protected $description = 'Process pending scheduled WhatsApp voucher deliveries';

    public function handle(VoucherDeliveryService $delivery): int
    {
        $this->info('Processing pending scheduled voucher deliveries...');
        $delivery->sendPendingLogs();
        $this->info('Scheduled deliveries processed successfully.');
        return Command::SUCCESS;
    }
}
