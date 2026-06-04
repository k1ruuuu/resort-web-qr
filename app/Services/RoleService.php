<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(
        private readonly AuditService $audit
    ) {}

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::query()->create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? 'web',
            ]);

            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            $this->audit->log('role.created', $role, null, $role->toArray());

            return $role;
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $oldValues = $role->toArray();

            $role->update([
                'name' => $data['name'],
            ]);

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            } else {
                $role->syncPermissions([]);
            }

            $this->audit->log('role.updated', $role, $oldValues, $role->fresh()->toArray());

            return $role;
        });
    }

    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role) {
            $oldValues = $role->toArray();
            $role->delete();
            $this->audit->log('role.deleted', $role, $oldValues, null);
        });
    }
}
