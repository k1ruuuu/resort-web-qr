<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create the export permission
        $permission = Permission::firstOrCreate(
            ['name' => 'reports.export'],
            ['guard_name' => 'web']
        );

        $this->command->info('✓ Created permission: reports.export');

        // Assign to super-admin role (if it exists)
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
            $this->command->info('✓ Assigned reports.export to super-admin role');
        }

        // Assign to admin role (if it exists)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permission);
            $this->command->info('✓ Assigned reports.export to admin role');
        }

        $this->command->info('');
        $this->command->info('Export permission setup complete!');
    }
}
