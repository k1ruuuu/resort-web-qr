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
                'facility_template_id' => $data['facility_template_id'],
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->audit->log('outlet.created', $outlet, null, $outlet->toArray());

            return $outlet;
        });
    }

    public function update(Outlet $outlet, array $data): Outlet
    {
        return DB::transaction(function () use ($outlet, $data) {
            $oldValues = $outlet->toArray();

            $outlet->update([
                'property_id' => $data['property_id'],
                'facility_template_id' => $data['facility_template_id'],
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->audit->log('outlet.updated', $outlet, $oldValues, $outlet->fresh()->toArray());

            return $outlet;
        });
    }

    public function delete(Outlet $outlet): void
    {
        DB::transaction(function () use ($outlet) {
            $oldValues = $outlet->toArray();
            $outlet->delete();
            $this->audit->log('outlet.deleted', $outlet, $oldValues, null);
        });
    }
}
