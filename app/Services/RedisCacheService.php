<?php

namespace App\Services;

use App\Models\GuestVoucher;
use App\Models\Booking;
use App\Models\Outlet;
use App\Models\FacilityTemplate;
use Illuminate\Support\Facades\Cache;

class RedisCacheService
{
    private const TTL_SHORT = 300;      // 5 minutes
    private const TTL_MEDIUM = 1800;    // 30 minutes
    private const TTL_LONG = 3600;      // 1 hour
    private const TTL_DAY = 86400;      // 24 hours

    /**
     * Cache voucher data for quick retrieval during scanning
     */
    public function cacheVoucher(GuestVoucher $voucher): void
    {
        $key = "voucher:data:{$voucher->id}";
        Cache::put($key, $voucher->load(['booking.guest', 'booking.room', 'booking.property']), self::TTL_MEDIUM);

        // Also cache by token for quick lookup
        if ($voucher->secure_token) {
            Cache::put("voucher:token:{$voucher->secure_token}", $voucher->id, self::TTL_MEDIUM);
        }
        if ($voucher->qr_code) {
            Cache::put("voucher:qr:{$voucher->qr_code}", $voucher->id, self::TTL_MEDIUM);
        }
    }

    /**
     * Get voucher from cache or database
     */
    public function getVoucherByToken(string $token): ?GuestVoucher
    {
        // Try to get voucher ID from cache
        $voucherId = Cache::remember(
            "voucher:token:{$token}",
            self::TTL_MEDIUM,
            function () use ($token) {
                $voucher = GuestVoucher::query()
                    ->where('secure_token', $token)
                    ->orWhere('qr_code', $token)
                    ->first();
                
                return $voucher?->id;
            }
        );

        if (!$voucherId) {
            return null;
        }

        // Get full voucher data from cache
        return Cache::remember(
            "voucher:data:{$voucherId}",
            self::TTL_MEDIUM,
            function () use ($voucherId) {
                return GuestVoucher::query()
                    ->with(['booking.guest', 'booking.room', 'booking.property'])
                    ->find($voucherId);
            }
        );
    }

    /**
     * Invalidate voucher cache
     */
    public function invalidateVoucher(GuestVoucher $voucher): void
    {
        Cache::forget("voucher:data:{$voucher->id}");
        if ($voucher->secure_token) {
            Cache::forget("voucher:token:{$voucher->secure_token}");
        }
        if ($voucher->qr_code) {
            Cache::forget("voucher:qr:{$voucher->qr_code}");
        }
        
        // Clear facility status cache
        Cache::forget("voucher:facilities:{$voucher->id}");
    }

    /**
     * Cache facility statuses for today
     */
    public function cacheFacilityStatuses(int $voucherId, string $date, $statuses): void
    {
        $key = "voucher:facilities:{$voucherId}:{$date}";
        Cache::put($key, $statuses, self::TTL_SHORT);
    }

    /**
     * Get cached facility statuses
     */
    public function getFacilityStatuses(int $voucherId, string $date)
    {
        return Cache::get("voucher:facilities:{$voucherId}:{$date}");
    }

    /**
     * Cache booking data
     */
    public function cacheBooking(Booking $booking): void
    {
        $key = "booking:data:{$booking->id}";
        Cache::put($key, $booking->load(['guest', 'property', 'room', 'bookingFacilities']), self::TTL_MEDIUM);
    }

    /**
     * Invalidate booking cache
     */
    public function invalidateBooking(Booking $booking): void
    {
        Cache::forget("booking:data:{$booking->id}");
        
        // Also invalidate related voucher
        if ($booking->guestVoucher) {
            $this->invalidateVoucher($booking->guestVoucher);
        }
    }

    /**
     * Cache outlet data
     */
    public function cacheOutlet(Outlet $outlet): void
    {
        $key = "outlet:data:{$outlet->id}";
        Cache::put($key, $outlet->load('facilityTemplate'), self::TTL_LONG);
    }

    /**
     * Get outlet from cache
     */
    public function getOutlet(int $outletId): ?Outlet
    {
        return Cache::remember(
            "outlet:data:{$outletId}",
            self::TTL_LONG,
            function () use ($outletId) {
                return Outlet::query()->with('facilityTemplate')->find($outletId);
            }
        );
    }

    /**
     * Cache active outlets list
     */
    public function cacheActiveOutlets(): void
    {
        $outlets = Outlet::query()
            ->where('is_active', true)
            ->with('facilityTemplate')
            ->orderBy('name')
            ->get();
        
        Cache::put('outlets:active', $outlets, self::TTL_LONG);
    }

    /**
     * Get active outlets from cache
     */
    public function getActiveOutlets()
    {
        return Cache::remember(
            'outlets:active',
            self::TTL_LONG,
            function () {
                return Outlet::query()
                    ->where('is_active', true)
                    ->with('facilityTemplate')
                    ->orderBy('name')
                    ->get();
            }
        );
    }

    /**
     * Track concurrent scans to prevent abuse
     */
    public function trackScan(string $qrCode, string $ipAddress): bool
    {
        $key = "scan:track:{$qrCode}:{$ipAddress}";
        $attempts = (int) Cache::get($key, 0);

        if ($attempts >= 5) { // Max 5 scans per minute per IP
            return false;
        }

        Cache::put($key, $attempts + 1, 60);
        return true;
    }

    /**
     * Cache redemption count for analytics
     */
    public function incrementRedemptionCount(int $facilityId, string $date): void
    {
        $key = "analytics:redemptions:{$facilityId}:{$date}";
        Cache::increment($key);
        Cache::expire($key, self::TTL_DAY);
    }

    /**
     * Get redemption count from cache
     */
    public function getRedemptionCount(int $facilityId, string $date): int
    {
        return (int) Cache::get("analytics:redemptions:{$facilityId}:{$date}", 0);
    }

    /**
     * Clear all caches (use with caution)
     */
    public function clearAllCaches(): void
    {
        Cache::flush();
    }
}
