<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables to allow fresh insert of rows
        $tables = [
            'audit_logs', 'booking_facilities', 'bookings', 'delivery_logs',
            'facility_template_outlet', 'facility_templates', 'guests',
            'guest_vouchers', 'import_logs', 'model_has_roles', 'outlets',
            'permissions', 'properties', 'property_user', 'qr_scan_logs',
            'redemption_logs', 'role_has_permissions', 'roles', 'rooms',
            'room_types', 'settings', 'users', 'areas',
        ];

        foreach ($tables as $table) {
            \Illuminate\Support\Facades\DB::table($table)->truncate();
        }

        // Path to the SQL file
        $sqlPath = database_path('seeders/data.sql');

        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            \Illuminate\Support\Facades\DB::unprepared($sql);
            $this->command->info('Database seeded from data.sql successfully.');
        } else {
            $this->command->error('File data.sql not found.');
        }

        // Re-enable foreign key checks
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
