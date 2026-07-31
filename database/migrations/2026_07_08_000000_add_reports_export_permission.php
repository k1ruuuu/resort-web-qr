<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('reports.export', 'web');

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->givePermissionTo($permission);

        $admin = Role::findOrCreate('admin', 'web');
        $admin->givePermissionTo($permission);
    }

    public function down(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::where('name', 'reports.export')->where('guard_name', 'web')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
