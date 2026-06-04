<?php

namespace App\Exceptions;

use Exception;

class VoucherException extends Exception
{
    public static function notFound(): self
    {
        return new self('Voucher not found.', 404);
    }

    public static function alreadyRedeemed(): self
    {
        return new self('Voucher already redeemed for this date.', 422);
    }

    public static function expired(): self
    {
        return new self('Voucher is expired or not valid today.', 422);
    }

    public static function invalidOutlet(): self
    {
        return new self('Outlet cannot redeem this facility.', 403);
    }

    public static function quotaExceeded(): self
    {
        return new self('Voucher quota exceeded.', 422);
    }

    public static function bookingNotCheckedIn(): self
    {
        return new self('Booking must be checked in before generating vouchers.', 422);
    }

    public static function noFacilities(): self
    {
        return new self('No facilities are linked to this booking. Add facilities on the booking or check in again after configuring facility templates.', 422);
    }

    public static function noEligibleFacilitiesForDate(string $date): self
    {
        return new self("No facilities are active for {$date}. Check stay dates and facility periods.", 422);
    }
}
