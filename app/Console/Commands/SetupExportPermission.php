<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetupExportPermission extends Command
{
    protected $signature = 'permission:setup-export';

    protected $description = 'Create reports.export permission and assign to admin roles';

    public function handle(): int
    {
        $this->info('Setting up export permission...');
        $this->newLine();

        // Create the export permission
        $permission = Permission::firstOrCreate(
            ['name' => 'reports.export'],
            ['guard_name' => 'web']
        );

        $this->info('✓ Created permission: reports.export');

        // Assign to super-admin role
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            if (!$superAdmin->hasPermissionTo($permission)) {
                $superAdmin->givePermissionTo($permission);
                $this->info('✓ Assigned to super-admin role');
            } else {
                $this->comment('  super-admin already has this permission');
            }
        }

        // Assign to admin role
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            if (!$admin->hasPermissionTo($permission)) {
                $admin->givePermissionTo($permission);
                $this->info('✓ Assigned to admin role');
            } else {
                $this->comment('  admin already has this permission');
            }
        }

        // Clear permission cache
        $this->call('permission:cache-reset');

        $this->newLine();
        $this->info('Export permission setup complete!');
        $this->info('You can now use the Excel export feature.');

        return Command::SUCCESS;
    }
}
