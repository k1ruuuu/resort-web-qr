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
     * Acquire a distributed lock for QR code uniqueness check
     */
    public function lockQrCodeGeneration(string $qrCode, int $seconds = 5): ?Lock
    {
        $lock = Cache::lock("qr:generate:{$qrCode}", $seconds);
        
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

    /**
     * Acquire a distributed lock for facility quota management
     */
    public function lockFacilityQuota(int $voucherId, int $facilityId, string $date, int $seconds = 5): ?Lock
    {
        $lock = Cache::lock("facility:quota:{$voucherId}:{$facilityId}:{$date}", $seconds);
        
        return $lock->get() ? $lock : null;
    }

    /**
     * Try to acquire a lock with callback execution
     */
    public function executeWithLock(string $lockKey, callable $callback, int $seconds = 10, int $waitSeconds = 10)
    {
        $lock = Cache::lock($lockKey, $seconds);

        try {
            if ($lock->block($waitSeconds)) {
                return $callback();
            }

            throw new \RuntimeException('Could not acquire lock after ' . $waitSeconds . ' seconds');
        } finally {
            $lock->release();
        }
    }
}
