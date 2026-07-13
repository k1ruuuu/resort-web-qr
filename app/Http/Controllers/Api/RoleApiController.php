<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use Spatie\Permission\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleApiController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorizePermission('roles.manage');

        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->paginate(request()->integer('per_page', 20));

        return $this->respondPaginated($roles);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorizePermission('roles.manage');

        $role->load('permissions');

        return $this->respond($role);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('roles.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::query()->create(['name' => $data['name'], 'guard_name' => 'web']);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->respondCreated($role->load('permissions'));
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorizePermission('roles.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update($data);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->respond($role->fresh()->load('permissions'));
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorizePermission('roles.manage');

        if ($role->name === 'admin') {
            return $this->respondError('The admin role cannot be deleted.', 422);
        }

        $role->delete();

        return $this->respondMessage('Role deleted successfully.');
    }

    public function permissions(): JsonResponse
    {
        $this->authorizePermission('roles.manage');

        return $this->respond(\Spatie\Permission\Models\Permission::query()->orderBy('name')->get());
    }
}
