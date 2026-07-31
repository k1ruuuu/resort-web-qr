<?php

namespace App\Imports;

use App\Models\Guest;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class GuestsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    protected $errors = [];
    protected $failures = [];
    protected $imported = 0;
    protected $skipped = 0;
    protected int $headingRow = 1;
    protected array $seenEmails = [];
    protected array $seenPhones = [];

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
        // Check if guest with same email or phone already exists
        $existing = null;
        
        if (!empty($row['email'])) {
            if (in_array($row['email'], $this->seenEmails, true)) {
                $this->skipped++;
                return null;
            }
            $existing = Guest::where('email', $row['email'])->first();
        }
        
        if (!$existing && !empty($row['phone'])) {
            if (in_array($row['phone'], $this->seenPhones, true)) {
                $this->skipped++;
                return null;
            }
            $existing = Guest::where('phone', $row['phone'])->first();
        }

        if ($existing) {
            $this->skipped++;
            return null;
        }

        if (!empty($row['email'])) {
            $this->seenEmails[] = $row['email'];
        }
        if (!empty($row['phone'])) {
            $this->seenPhones[] = $row['phone'];
        }

        if ($existing) {
            $this->skipped++;
            return null;
        }

        $this->imported++;

        return new Guest([
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'email' => !empty($row['email']) ? $row['email'] : null,
            'phone' => !empty($row['phone']) ? $row['phone'] : null,
            'whatsapp' => !empty($row['whatsapp']) ? $row['whatsapp'] : null,
            'document_id' => !empty($row['document_id']) ? $row['document_id'] : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'document_id' => ['nullable', 'string', 'max:64'],
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
