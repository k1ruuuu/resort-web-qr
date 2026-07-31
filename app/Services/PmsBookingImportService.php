<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PmsBookingImportService
{
    public function __construct(
        private readonly BookingService $bookings,
    ) {}

    public function importRow(Property $property, array $row): Booking
    {
        $guestName = trim((string) ($row['nama'] ?? $row['Nama'] ?? 'Guest'));
        [$firstName, $lastName] = $this->splitName($guestName);

        $guest = Guest::query()->firstOrCreate(
            ['first_name' => $firstName, 'last_name' => $lastName, 'phone' => $row['whatsapp'] ?? ''],
            ['whatsapp' => $row['whatsapp'] ?? null]
        );

        $room = $this->resolveRoom($property, (string) ($row['kamar'] ?? $row['Kamar'] ?? ''));
        [$adults, $children] = $this->parsePax((string) ($row['jumlah'] ?? $row['Jumlah'] ?? '2 Pax'));
        $checkIn = $this->parseDate($row['check_in'] ?? $row['Check In'] ?? $row['expected_arrival'] ?? $row['Expected Arrival'] ?? null);
        $checkOut = $this->parseDate($row['check_out'] ?? $row['Check Out'] ?? $row['expected_departure'] ?? $row['Expected Departure'] ?? null);

        $status = $this->mapStatus((string) ($row['status'] ?? $row['Status Booking'] ?? ''));

        return $this->bookings->create([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'room_id' => $room?->id,
            'room_label' => $room?->label ?? (string) ($row['kamar'] ?? ''),
            'booking_code' => $this->normalizeBookingCode($row['kode_booking'] ?? $row['Kode Booking'] ?? null),
            'reference' => strtoupper(Str::random(8)),
            'source' => (string) ($row['source'] ?? $row['Source'] ?? null),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'expected_arrival' => $this->parseDate($row['expected_arrival'] ?? $row['Expected Arrival'] ?? null) ?? $checkIn,
            'expected_departure' => $this->parseDate($row['expected_departure'] ?? $row['Expected Departure'] ?? null) ?? $checkOut,
            'adults' => $adults,
            'children' => $children,
            'total_pax' => $adults + $children,
            'nights' => max(1, Carbon::parse($checkIn)->startOfDay()->diffInDays(Carbon::parse($checkOut)->startOfDay())),
            'status' => $status,
            'checked_in_at' => $this->parseDateTime($row['check_in'] ?? $row['Check In'] ?? null),
            'pms_voucher_ref' => $this->normalizeVoucherRef($row['voucher'] ?? $row['Voucher'] ?? null),
        ]);
    }

    private function resolveRoom(Property $property, string $kamarLabel): ?Room
    {
        if ($kamarLabel === '') {
            return null;
        }

        $code = Str::before($kamarLabel, ' -');
        $code = trim($code) ?: $kamarLabel;

        return Room::query()
            ->where('property_id', $property->id)
            ->where(function ($q) use ($kamarLabel, $code) {
                $q->where('label', $kamarLabel)->orWhere('code', $code)->orWhere('number', $code);
            })
            ->first();
    }

    private function mapStatus(string $status): BookingStatus
    {
        return match (strtolower($status)) {
            'check in', 'checked in', 'check_in', 'checked_in' => BookingStatus::CheckIn,
            'check out', 'checked out', 'check_out', 'checked_out', 'expected_departure' => BookingStatus::ExpectedDeparture,
            'cancelled', 'canceled' => BookingStatus::Cancelled,
            default => BookingStatus::ExpectedArrival,
        };
    }

    private function parsePax(string $jumlah): array
    {
        preg_match('/(\d+)\s*Pax/i', $jumlah, $matches);
        $pax = isset($matches[1]) ? (int) $matches[1] : 2;

        return [$pax, 0];
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestampUTC(((float) $value - 25569) * 86400)->toDateString();
        }

        $value = str_replace('/', '-', (string) $value);

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [$parts[0] ?? 'Guest', $parts[1] ?? '-'];
    }

    private function normalizeBookingCode(mixed $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $code = trim((string) $code);

        if (is_numeric($code)) {
            return (string) (int) ((float) $code);
        }

        return $code;
    }

    private function normalizeVoucherRef(mixed $ref): ?string
    {
        if ($ref === null || $ref === '' || $ref === '-') {
            return null;
        }

        $ref = trim((string) $ref);

        if (is_numeric($ref)) {
            return (string) (int) ((float) $ref);
        }

        return $ref;
    }
}
