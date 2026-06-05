<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.manage',
            'roles.manage',
            'facilities.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->givePermissionTo($permissions);
    }

    public function down(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.manage',
            'roles.manage',
            'facilities.manage',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->where('guard_name', 'web')->first();
            if ($permission) {
                $permission->delete();
            }
        }
    }
};
