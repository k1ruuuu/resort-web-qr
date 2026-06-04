<?php

namespace App\Services;

use App\Models\Booking;

/**
 * Client spec: max facility usage = room capacity + extra beds.
 */
class StayQuotaService
{
    public function quotaForBooking(Booking $booking): int
    {
        $booking->loadMissing(['room.roomType']);

        $capacity = $booking->room?->capacity
            ?? $booking->room?->roomType?->max_occupancy
            ?? $booking->total_pax;

        return max(1, (int) $capacity + (int) $booking->extra_beds);
    }
}
