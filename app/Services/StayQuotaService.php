<?php

namespace App\Services;

use App\Models\Booking;

class StayQuotaService
{
    public function quotaForBooking(Booking $booking): int
    {
        return (int) ($booking->total_pax + $booking->extra_beds);
    }
}
