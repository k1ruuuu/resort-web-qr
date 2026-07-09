<?php

namespace App\Imports;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Room;
use App\Enums\BookingStatus;
use App\Services\VoucherService;
use App\Services\BookingService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
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
    protected array $processedReferences = [];
    protected array $checkedInBookings = [];

    public function __construct(int $headingRow = 1)
    {
        $this->headingRow = $headingRow;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function model(array $row)
    {
        // Check if booking with same reference already exists (database or current batch)
        if (!empty($row['reference'])) {
            if (in_array($row['reference'], $this->processedReferences)) {
                $this->skipped++;
                return null;
            }
            
            $existing = Booking::where('reference', $row['reference'])->first();
            if ($existing) {
                $this->skipped++;
                return null;
            }
            
            $this->processedReferences[] = $row['reference'];
        }

        // Find guest by email or create new
        $guest = null;
        if (!empty($row['guest_email'])) {
            $guest = Guest::where('email', $row['guest_email'])->first();
        }

        if (!$guest && (!empty($row['guest_first_name']) || !empty($row['guest_last_name']))) {
            $guest = Guest::create([
                'first_name' => $row['guest_first_name'] ?? '',
                'last_name' => $row['guest_last_name'] ?? '',
                'email' => !empty($row['guest_email']) ? $row['guest_email'] : null,
                'phone' => !empty($row['guest_phone']) ? $row['guest_phone'] : null,
            ]);
        }

        if (!$guest) {
            $this->failures[] = [
                'row' => 'N/A',
                'attribute' => 'guest',
                'errors' => ['Guest information is required'],
                'values' => $row,
            ];
            $this->skipped++;
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
                'row' => 'N/A',
                'attribute' => 'property',
                'errors' => ['Property not found'],
                'values' => $row,
            ];
            $this->skipped++;
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
                'row' => 'N/A',
                'attribute' => 'dates',
                'errors' => ['Invalid date format'],
                'values' => $row,
            ];
            $this->skipped++;
            return null;
        }

        if (!$checkIn || !$checkOut) {
            $this->failures[] = [
                'row' => 'N/A',
                'attribute' => 'dates',
                'errors' => ['Check-in and check-out dates are required'],
                'values' => $row,
            ];
            $this->skipped++;
            return null;
        }

        // Calculate nights
        $nights = $checkIn->diffInDays($checkOut);

        // Parse status
        $status = BookingStatus::Pending;
        if (!empty($row['status'])) {
            $statusValue = strtolower(str_replace(' ', '_', $row['status']));
            $status = BookingStatus::tryFrom($statusValue) ?? BookingStatus::Pending;
        }

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
        if ($status === BookingStatus::CheckedIn) {
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
        if (empty($data['status']) && !empty($data['reservation_status'])) {
            $resStatus = trim($data['reservation_status']);
            if ($resStatus === '1' || strtolower($resStatus) === 'confirmed' || strtolower($resStatus) === 'checked in' || strtolower($resStatus) === 'checked_in') {
                $data['status'] = 'checked_in';
            } else {
                $data['status'] = 'pending';
            }
        }

        return $data;
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
}
