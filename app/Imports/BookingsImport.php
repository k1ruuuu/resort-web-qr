<?php

namespace App\Imports;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Room;
use App\Enums\BookingStatus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use Throwable;

class BookingsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    protected $errors = [];
    protected $failures = [];
    protected $imported = 0;
    protected $skipped = 0;
    protected int $headingRow = 1;
    protected int $currentRowNumber = 1;
    protected array $processedReferences = [];
    protected array $checkedInBookings = [];

    public function __construct(int $headingRow = 1)
    {
        $this->headingRow = $headingRow;
        $this->currentRowNumber = $headingRow;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function model(array $row)
    {
        $this->currentRowNumber++;
        $rowNum = $this->currentRowNumber;

        // Check if booking with same reference or booking_code already exists (database including trashed or current batch)
        $ref = !empty($row['reference']) ? trim((string) $row['reference']) : null;
        $code = !empty($row['booking_code']) ? trim((string) $row['booking_code']) : null;

        if ($ref && in_array($ref, $this->processedReferences, true)) {
            $this->skipped++;
            return null;
        }

        if ($ref || $code) {
            $existing = Booking::withTrashed()
                ->where(function ($q) use ($ref, $code) {
                    if ($ref) {
                        $q->where('reference', $ref);
                    }
                    if ($code) {
                        $ref ? $q->orWhere('booking_code', $code) : $q->where('booking_code', $code);
                    }
                })
                ->first();

            if ($existing) {
                $this->skipped++;
                return null;
            }
        }

        if ($ref) {
            $this->processedReferences[] = $ref;
        }

        // Find guest by email, phone, or name before creating new to prevent duplicate guest profiles
        $guest = null;
        if (!empty($row['guest_email'])) {
            $guest = Guest::where('email', $row['guest_email'])->first();
        }

        if (!$guest && !empty($row['guest_phone'])) {
            $rawPhone = trim((string) $row['guest_phone']);
            $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
            $guest = Guest::where('phone', $rawPhone)
                ->orWhere('whatsapp', $rawPhone)
                ->when(!empty($cleanPhone), function ($q) use ($cleanPhone) {
                    $q->orWhere('phone', 'like', "%{$cleanPhone}%")
                      ->orWhere('whatsapp', 'like', "%{$cleanPhone}%");
                })
                ->first();
        }

        if (!$guest && (!empty($row['guest_first_name']) || !empty($row['guest_last_name']))) {
            $firstName = trim((string) ($row['guest_first_name'] ?? ''));
            $lastName = trim((string) ($row['guest_last_name'] ?? ''));
            if ($firstName !== '' && $lastName !== '') {
                $guest = Guest::where('first_name', $firstName)
                    ->where('last_name', $lastName)
                    ->first();
            }
        }

        if (!$guest && (!empty($row['guest_first_name']) || !empty($row['guest_last_name']))) {
            $guest = Guest::create([
                'first_name' => $this->sanitizeCell($row['guest_first_name'] ?? ''),
                'last_name' => $this->sanitizeCell($row['guest_last_name'] ?? ''),
                'email' => !empty($row['guest_email']) ? $this->sanitizeCell($row['guest_email']) : null,
                'phone' => !empty($row['guest_phone']) ? $this->sanitizeCell($row['guest_phone']) : null,
            ]);
        }

        if (!$guest) {
            $this->failures[] = [
                'row' => $rowNum,
                'attribute' => 'guest',
                'errors' => ['Guest information is required'],
                'values' => $row,
            ];
            return null;
        }

        // Find property
        $property = null;
        if (!empty($row['property_name'])) {
            $property = Property::where('name', 'like', '%' . $row['property_name'] . '%')->first();
        } elseif (!empty($row['property_id'])) {
            $property = Property::find($row['property_id']);
        }

        if (!$property) {
            $this->failures[] = [
                'row' => $rowNum,
                'attribute' => 'property',
                'errors' => ['Property not found'],
                'values' => $row,
            ];
            return null;
        }

        // Find room (optional)
        $room = null;
        if (!empty($row['room_number'])) {
            $room = Room::where('number', $row['room_number'])
                ->where('property_id', $property->id)
                ->first();
        } elseif (!empty($row['room_id'])) {
            $room = Room::find($row['room_id']);
        }

        // Parse dates
        try {
            $checkIn = $this->parseDate($row['check_in'] ?? null);
            $checkOut = $this->parseDate($row['check_out'] ?? null);
        } catch (\Exception $e) {
            $this->failures[] = [
                'row' => $rowNum,
                'attribute' => 'dates',
                'errors' => ['Invalid date format'],
                'values' => $row,
            ];
            return null;
        }

        if (!$checkIn || !$checkOut) {
            $this->failures[] = [
                'row' => $rowNum,
                'attribute' => 'dates',
                'errors' => ['Check-in and check-out dates are required'],
                'values' => $row,
            ];
            return null;
        }

        // Calculate nights
        $nights = $checkIn->copy()->startOfDay()->diffInDays($checkOut->copy()->startOfDay());

        // Parse status
        $rawStatus = $row['status'] ?? $row['reservation_status'] ?? null;
        $status = $this->mapStatus($rawStatus instanceof BookingStatus ? $rawStatus->value : (string) $rawStatus);

        $this->imported++;

        $booking = new Booking([
            'property_id' => $property->id,
            'guest_id' => $guest->id,
            'room_id' => $room?->id,
            'booking_code' => $row['booking_code'] ?? null,
            'reference' => $row['reference'] ?? 'IMP-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'source' => $row['source'] ?? 'import',
            'room_label' => $row['room_label'] ?? $row['room_number'] ?? $room?->number ?? null,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'expected_arrival' => !empty($row['expected_arrival']) ? $this->parseDate($row['expected_arrival']) : $checkIn,
            'expected_departure' => !empty($row['expected_departure']) ? $this->parseDate($row['expected_departure']) : $checkOut,
            'nights' => $nights,
            'adults' => (int) ($row['adults'] ?? 2),
            'children' => (int) ($row['children'] ?? 0),
            'extra_beds' => (int) ($row['extra_beds'] ?? 0),
            'total_pax' => (int) ($row['total_pax'] ?? ($row['adults'] ?? 2) + ($row['children'] ?? 0)),
            'status' => $status,
            'arrangement_code' => $row['arrangement_code'] ?? null,
            'pms_voucher_ref' => $row['pms_voucher_ref'] ?? null,
        ]);

        // Track checked-in bookings for voucher generation
                if ($status === BookingStatus::CheckIn) {
            $this->checkedInBookings[] = $booking;
        }

        return $booking;
    }

    public function prepareForValidation($data, $index)
    {
        // 1. Property mapping fallback
        if (empty($data['property_name']) && empty($data['property_id'])) {
            $property = Property::where('is_active', true)->first();
            if ($property) {
                $data['property_id'] = $property->id;
                $data['property_name'] = $property->name;
            }
        }

        // 2. Dates mapping
        if (empty($data['check_in']) && !empty($data['arrival'])) {
            $data['check_in'] = $data['arrival'];
        }
        if (empty($data['check_out']) && !empty($data['departure'])) {
            $data['check_out'] = $data['departure'];
        }

        // 3. Reference and Booking Code mapping
        if (empty($data['reference']) && !empty($data['rsv_no'])) {
            $ref = (string) $data['rsv_no'];
            if (!empty($data['room_number'])) {
                $roomSuffix = str_replace(' ', '', $data['room_number']);
                $ref .= '-' . $roomSuffix;
            }
            $data['reference'] = $ref;
        }
        if (empty($data['booking_code']) && !empty($data['rsv_no'])) {
            $code = (string) $data['rsv_no'];
            if (!empty($data['room_number'])) {
                $roomSuffix = str_replace(' ', '', $data['room_number']);
                $code .= '-' . $roomSuffix;
            }
            $data['booking_code'] = $code;
        }

        // 4. Guest name splitting
        if (empty($data['guest_first_name']) && empty($data['guest_last_name']) && !empty($data['guest_name'])) {
            $parts = explode(',', $data['guest_name']);
            if (count($parts) > 1) {
                // LAST_NAME, FIRST_NAME
                $data['guest_first_name'] = trim($parts[1]);
                $data['guest_last_name'] = trim($parts[0]);
            } else {
                $data['guest_first_name'] = trim($data['guest_name']);
                $data['guest_last_name'] = '';
            }
        }

        // 5. Pax mapping
        if (empty($data['adults']) && !empty($data['adult'])) {
            $data['adults'] = (int) $data['adult'];
        }
        if (empty($data['children']) && !empty($data['child'])) {
            $data['children'] = (int) $data['child'];
        }

        // 6. Status mapping
        $rawStatus = $data['status'] ?? $data['reservation_status'] ?? $data['res_status'] ?? $data['booking_status'] ?? null;
        $data['status'] = $this->mapStatus($rawStatus ? (string) $rawStatus : null)->value;

        return $data;
    }

    protected function mapStatus(?string $rawStatus): BookingStatus
    {
        if (empty($rawStatus)) {
            return BookingStatus::ExpectedArrival;
        }

        $cleaned = strtolower(trim($rawStatus));
        $normalized = str_replace([' ', '-'], '_', $cleaned);

        return match ($normalized) {
            'check_in', 'checked_in', 'in_house', 'inhouse' => BookingStatus::CheckIn,
            'check_out', 'checked_out', 'expected_departure', 'departed' => BookingStatus::ExpectedDeparture,
            'cancelled', 'canceled' => BookingStatus::Cancelled,
            default => BookingStatus::ExpectedArrival,
        };
    }

    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:100'],
            'booking_code' => ['nullable', 'string', 'max:100'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_first_name' => ['nullable', 'string', 'max:100'],
            'guest_last_name' => ['nullable', 'string', 'max:100'],
            'guest_phone' => ['nullable', 'string', 'max:32'],
            'property_name' => ['nullable', 'string'],
            'property_id' => ['nullable', 'integer'],
            'room_number' => ['nullable', 'string'],
            'room_id' => ['nullable', 'integer'],
            'check_in' => ['required'],
            'check_out' => ['required'],
            'adults' => ['nullable', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'total_pax' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Handle Excel date serial number
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }

            $value = trim($value);
            
            // Check for DD/MM/YY or DD/MM/YYYY formats
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}$/', $value)) {
                $parts = explode('/', $value);
                $day = (int)$parts[0];
                $month = (int)$parts[1];
                $year = $parts[2];
                
                if (strlen($year) === 2) {
                    $year = '20' . $year;
                }
                
                return Carbon::createFromDate((int)$year, $month, $day)->startOfDay();
            }

            // Try parsing various date formats
            return Carbon::parse($value);
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format: {$value}");
        }
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }


    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getImported(): int
    {
        return $this->imported;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function getCheckedInBookings(): array
    {
        return $this->checkedInBookings;
    }

    /** Prefix spreadsheet formula triggers (=,+,-,@) so exports can't execute on open. */
    protected function sanitizeCell(mixed $value): mixed
    {
        if (!is_string($value) || $value === '' || !in_array($value[0], ['=', '+', '-', '@'], true)) {
            return $value;
        }

        return "'" . $value;
    }
}
