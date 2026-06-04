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
        $this->roleService->update($role, $request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizePermission('roles.manage');

        if ($role->name === 'admin') {
            return redirect()
                ->route('roles.index')
                ->with('error', 'The admin role cannot be deleted.');
        }

        $this->roleService->delete($role);

        return redirect()
            ->route('roles.index')
            ->with('success', "Role '{$role->name}' deleted successfully.");
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
