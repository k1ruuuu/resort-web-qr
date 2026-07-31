<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('vouchers.generate', 'web');

        // Check which permissions exist
        $redeemExists = Permission::where('name', 'vouchers.redeem')->where('guard_name', 'web')->exists();
        $viewExists = Permission::where('name', 'vouchers.view')->where('guard_name', 'web')->exists();

        foreach (Role::query()->get() as $role) {
            $hasRelevantPermission = $role->hasPermissionTo('vouchers.generate');
            
            if (!$hasRelevantPermission && $redeemExists) {
                $hasRelevantPermission = $role->hasPermissionTo('vouchers.redeem');
            }
            
            if (!$hasRelevantPermission && $viewExists) {
                $hasRelevantPermission = $role->hasPermissionTo('vouchers.view');
            }

            if ($hasRelevantPermission) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        $permission = Permission::findByName('vouchers.generate', 'web');

        if ($permission) {
            foreach (Role::query()->get() as $role) {
                $role->revokePermissionTo($permission);
            }
        }
    }
};
