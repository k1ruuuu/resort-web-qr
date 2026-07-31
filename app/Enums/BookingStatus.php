<?php

namespace App\Enums;

enum BookingStatus: string
{
    case ExpectedArrival = 'expected_arrival';
    case CheckIn = 'check_in';
    case ExpectedDeparture = 'expected_departure';
    case Cancelled = 'cancelled';
}
