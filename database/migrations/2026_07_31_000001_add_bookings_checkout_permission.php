<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('bookings.checkout', 'web');

        // Grant to any role that already has the bookings.checkin permission
        // so existing databases (seeded before this permission existed) work.
        // We first check if the bookings.checkin permission exists, because during a fresh migration
        // before seeders run, it does not exist yet.
        $checkinPermissionExists = Permission::where('name', 'bookings.checkin')->where('guard_name', 'web')->exists();
        
        if ($checkinPermissionExists) {
            foreach (Role::query()->get() as $role) {
                if ($role->hasPermissionTo('bookings.checkin')) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    public function down(): void
    {
        $permission = Permission::findByName('bookings.checkout', 'web');

        if ($permission) {
            foreach (Role::query()->get() as $role) {
                $role->revokePermissionTo($permission);
            }
        }
    }
};
