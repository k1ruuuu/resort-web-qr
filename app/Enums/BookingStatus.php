<?php

namespace App\Enums;

enum BookingStatus: string
{
    case ConfirmedReservation = 'confirmed_reservation';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case Pending = 'pending';
}
