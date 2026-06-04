<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\FacilityTemplate;
use Carbon\Carbon;

 # QR-Code = Nama-Room + Tgl.Fasilitas
class QrCodePayload
{
    public static function build(Booking $booking, FacilityTemplate $facility, Carbon $date): string
    {
        $room = $booking->room_label
            ?? $booking->room?->label
            ?? $booking->room?->number
            ?? 'ROOM';

        return sprintf(
            '%s+%s+%s+%s',
            $booking->id,
            $room,
            $facility->code,
            $date->format('Y-m-d'),
        );
    }

    public static function parse(string $qrCode): ?array
    {
        $parts = explode('+', $qrCode, 4);

        if (count($parts) !== 4) {
            return null;
        }

        return [
            'booking_id' => $parts[0],
            'room_label' => $parts[1],
            'facility_code' => $parts[2],
            'valid_date' => $parts[3],
        ];
    }
}
