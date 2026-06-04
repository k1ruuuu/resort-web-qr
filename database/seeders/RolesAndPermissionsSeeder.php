<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'bookings.view',
            'bookings.create',
            'bookings.checkin',
            'vouchers.view',
            'vouchers.generate',
            'vouchers.redeem',
            'guests.manage',
            'properties.manage',
            'rooms.manage',
            'reports.view',
            'users.manage',
            'roles.manage',
            'facilities.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($permissions);

        $frontDesk = Role::findOrCreate('front-desk', 'web');
        $frontDesk->syncPermissions([
            'bookings.view',
            'bookings.create',
            'bookings.checkin',
            'vouchers.view',
            'vouchers.generate',
            'guests.manage',
            'reports.view',
        ]);

        $outlet = Role::findOrCreate('outlet-staff', 'web');
        $outlet->syncPermissions([
            'vouchers.view',
            'vouchers.redeem',
        ]);

        $user = User::query()->firstOrCreate(
            ['email' => 'admin@resort.local'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $user->assignRole('admin');
    }
}
