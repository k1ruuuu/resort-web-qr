<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService
    ) {}

    public function index(): View
    {
        $this->authorizePermission('roles.manage');

        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->paginate(20);

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->authorizePermission('roles.manage');

        $permissions = Permission::query()->orderBy('name')->get();

        return view('roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create($request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', "Role '{$role->name}' created successfully.");
    }

    public function edit(Role $role): View
    {
        $this->authorizePermission('roles.manage');

        $permissions = Permission::query()->orderBy('name')->get();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();

        if (in_array($role->name, ['admin', 'super-admin']) && isset($data['name']) && $data['name'] !== $role->name) {
            return redirect()
                ->route('roles.index')
                ->with('error', "The '{$role->name}' role name cannot be changed.");
        }

        $this->roleService->update($role, $data);

        return redirect()
            ->route('roles.index')
            ->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizePermission('roles.manage');

        if (in_array($role->name, ['admin', 'super-admin'])) {
            return redirect()
                ->route('roles.index')
                ->with('error', "The '{$role->name}' role cannot be deleted.");
        }

        $this->roleService->delete($role);

        return redirect()
            ->route('roles.index')
            ->with('success', "Role '{$role->name}' deleted successfully.");
    }
}
