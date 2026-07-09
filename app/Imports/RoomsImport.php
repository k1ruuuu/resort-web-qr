<?php

namespace App\Imports;

use App\Models\Area;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class RoomsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    protected $errors = [];
    protected $failures = [];
    protected $imported = 0;
    protected $skipped = 0;
    protected int $headingRow = 1;

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
        $property = $this->resolveProperty($row);
        if (!$property) {
            $this->failures[] = [
                'row' => 'N/A',
                'attribute' => 'property',
                'errors' => ['Property not found. Provide property_name or property_id.'],
                'values' => $row,
            ];
            $this->skipped++;

            return null;
        }

        $number = trim((string) ($row['number'] ?? ''));
        if ($number === '') {
            $this->failures[] = [
                'row' => 'N/A',
                'attribute' => 'number',
                'errors' => ['Room number is required.'],
                'values' => $row,
            ];
            $this->skipped++;

            return null;
        }

        $existing = Room::query()
            ->where('property_id', $property->id)
            ->where('number', $number)
            ->first();

        if ($existing) {
            $this->skipped++;

            return null;
        }

        $roomType = $this->resolveRoomType($row, $property->id);
        if (!$roomType) {
            $this->failures[] = [
                'row' => 'N/A',
                'attribute' => 'room_type',
                'errors' => ['Room type not found for this property. Provide room_type_name or room_type_id.'],
                'values' => $row,
            ];
            $this->skipped++;

            return null;
        }

        $area = $this->resolveArea($row, $property->id);

        $status = strtolower(trim((string) ($row['status'] ?? 'available')));
        if (!in_array($status, ['available', 'occupied', 'maintenance'], true)) {
            $status = 'available';
        }

        $this->imported++;

        return new Room([
            'property_id' => $property->id,
            'area_id' => $area?->id,
            'room_type_id' => $roomType->id,
            'number' => $number,
            'code' => !empty($row['code']) ? trim((string) $row['code']) : null,
            'label' => !empty($row['label']) ? trim((string) $row['label']) : null,
            'capacity' => !empty($row['capacity']) ? (int) $row['capacity'] : 2,
            'status' => $status,
        ]);
    }

    protected function resolveProperty(array $row): ?Property
    {
        if (!empty($row['property_id'])) {
            return Property::find($row['property_id']);
        }

        if (!empty($row['property_name'])) {
            return Property::query()
                ->where('name', 'like', '%' . trim((string) $row['property_name']) . '%')
                ->first();
        }

        return null;
    }

    protected function resolveRoomType(array $row, int $propertyId): ?RoomType
    {
        if (!empty($row['room_type_id'])) {
            return RoomType::query()
                ->where('property_id', $propertyId)
                ->where('id', $row['room_type_id'])
                ->first();
        }

        if (!empty($row['room_type_name'])) {
            return RoomType::query()
                ->where('property_id', $propertyId)
                ->where('name', 'like', '%' . trim((string) $row['room_type_name']) . '%')
                ->first();
        }

        if (!empty($row['room_type_code'])) {
            return RoomType::query()
                ->where('property_id', $propertyId)
                ->where('code', trim((string) $row['room_type_code']))
                ->first();
        }

        return null;
    }

    protected function resolveArea(array $row, int $propertyId): ?Area
    {
        if (!empty($row['area_id'])) {
            return Area::query()
                ->where('property_id', $propertyId)
                ->where('id', $row['area_id'])
                ->first();
        }

        if (!empty($row['area_name'])) {
            return Area::query()
                ->where('property_id', $propertyId)
                ->where('name', 'like', '%' . trim((string) $row['area_name']) . '%')
                ->first();
        }

        if (!empty($row['area_code'])) {
            return Area::query()
                ->where('property_id', $propertyId)
                ->where('code', trim((string) $row['area_code']))
                ->first();
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:100'],
            'property_name' => ['nullable', 'string'],
            'property_id' => ['nullable', 'integer'],
            'room_type_name' => ['nullable', 'string'],
            'room_type_id' => ['nullable', 'integer'],
            'room_type_code' => ['nullable', 'string'],
            'area_name' => ['nullable', 'string'],
            'area_id' => ['nullable', 'integer'],
            'area_code' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:available,occupied,maintenance'],
        ];
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

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
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
}
