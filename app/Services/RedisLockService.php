<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Lock;

class RedisLockService
{
    /**
     * Acquire a distributed lock for voucher generation
     */
    public function lockVoucherGeneration(int $bookingId, int $seconds = 10): ?Lock
    {
        $lock = Cache::lock("voucher:generate:{$bookingId}", $seconds);
        
        return $lock->get() ? $lock : null;
    }

    /**
     * Acquire a distributed lock for voucher redemption
     */
    public function lockVoucherRedemption(int $voucherId, int $seconds = 10): ?Lock
    {
        $lock = Cache::lock("voucher:redeem:{$voucherId}", $seconds);
        
        return $lock->get() ? $lock : null;
    }

    /**
     * Acquire a distributed lock for booking check-in
     */
    public function lockBookingCheckIn(int $bookingId, int $seconds = 10): ?Lock
    {
        $lock = Cache::lock("booking:checkin:{$bookingId}", $seconds);
        
        return $lock->get() ? $lock : null;
    }
}
