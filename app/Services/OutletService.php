<?php

namespace App\Services;

use App\Models\Outlet;
use Illuminate\Support\Facades\DB;

class OutletService
{
    public function __construct(
        private readonly AuditService $audit
    ) {}

    public function create(array $data): Outlet
    {
        return DB::transaction(function () use ($data) {
            $outlet = Outlet::query()->create([
                'property_id' => $data['property_id'],
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if (!empty($data['facility_template_ids'])) {
                $outlet->facilityTemplates()->sync($data['facility_template_ids']);
            }

            $this->audit->log('outlet.created', $outlet, null, $outlet->toArray());

            return $outlet;
        });
    }

    public function update(Outlet $outlet, array $data): Outlet
    {
        return DB::transaction(function () use ($outlet, $data) {
            $oldValues = $outlet->fresh()->load('facilityTemplates')->toArray();

            $outlet->update([
                'property_id' => $data['property_id'],
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if (isset($data['facility_template_ids'])) {
                $outlet->facilityTemplates()->sync($data['facility_template_ids']);
            }

            $this->audit->log('outlet.updated', $outlet, $oldValues, $outlet->fresh()->load('facilityTemplates')->toArray());

            return $outlet;
        });
    }

    public function delete(Outlet $outlet): void
    {
        DB::transaction(function () use ($outlet) {
            $oldValues = $outlet->toArray();
            $outlet->facilityTemplates()->detach();
            $outlet->delete();
            $this->audit->log('outlet.deleted', $outlet, $oldValues, null);
        });
    }
}
