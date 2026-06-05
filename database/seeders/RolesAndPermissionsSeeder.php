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
            'delivery_settings.manage',
            'voucher_settings.manage',
            'vouchers.resend',
            'delivery_logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->syncPermissions($permissions);

        $admin = Role::findOrCreate('admin', 'web');
        $adminPermissions = array_filter($permissions, function ($p) {
            return $p !== 'voucher_settings.manage';
        });
        $admin->syncPermissions($adminPermissions);

        $staffPermissions = [
            'vouchers.view',
            'vouchers.redeem',
        ];

        $afternoonTea = Role::findOrCreate('afternoon-tea-staff', 'web');
        $afternoonTea->syncPermissions($staffPermissions);

        $breakfast = Role::findOrCreate('breakfast-staff', 'web');
        $breakfast->syncPermissions($staffPermissions);

        $dinner = Role::findOrCreate('dinner-staff', 'web');
        $dinner->syncPermissions($staffPermissions);

        $dreamJournaling = Role::findOrCreate('dream-journaling-staff', 'web');
        $dreamJournaling->syncPermissions($staffPermissions);

        $animalFeeding = Role::findOrCreate('animal-feeding-staff', 'web');
        $animalFeeding->syncPermissions($staffPermissions);

        $welcomeSnack = Role::findOrCreate('welcome-snack-staff', 'web');
        $welcomeSnack->syncPermissions($staffPermissions);

        $user = User::query()->firstOrCreate(
            ['email' => 'admin@resort.local'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $user->assignRole('super-admin');
    }
}
