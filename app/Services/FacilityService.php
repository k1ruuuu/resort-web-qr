<?php

namespace App\Services;

use App\Models\FacilityTemplate;
use Illuminate\Support\Facades\DB;

class FacilityService
{
    public function __construct(
        private readonly AuditService $audit
    ) {}

    public function create(array $data): FacilityTemplate
    {
        return DB::transaction(function () use ($data) {
            $facility = FacilityTemplate::query()->create([
                'property_id' => $data['property_id'],
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);

            $this->audit->log('facility.created', $facility, null, $facility->toArray());

            return $facility;
        });
    }

    public function update(FacilityTemplate $facility, array $data): FacilityTemplate
    {
        return DB::transaction(function () use ($facility, $data) {
            $oldValues = $facility->toArray();

            $facility->update([
                'property_id' => $data['property_id'],
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);

            $this->audit->log('facility.updated', $facility, $oldValues, $facility->fresh()->toArray());

            return $facility;
        });
    }

    public function delete(FacilityTemplate $facility): void
    {
        DB::transaction(function () use ($facility) {
            $oldValues = $facility->toArray();
            $facility->delete();
            $this->audit->log('facility.deleted', $facility, $oldValues, null);
        });
    }
}
