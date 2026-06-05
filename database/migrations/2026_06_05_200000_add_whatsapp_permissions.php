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
            'delivery_settings.manage',
            'voucher_settings.manage',
            'vouchers.resend',
            'delivery_logs.view',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->givePermissionTo($permissions);

        $admin = Role::findOrCreate('admin', 'web');
        $admin->givePermissionTo([
            'delivery_settings.manage',
            'vouchers.resend',
            'delivery_logs.view',
        ]);
    }

    public function down(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'delivery_settings.manage',
            'voucher_settings.manage',
            'vouchers.resend',
            'delivery_logs.view',
        ];

        foreach ($permissions as $name) {
            $p = Permission::where('name', $name)->where('guard_name', 'web')->first();
            if ($p) {
                $p->delete();
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->delete();
        }
    }
};
