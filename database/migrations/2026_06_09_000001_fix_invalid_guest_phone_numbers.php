<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix guest ID 4 with invalid phone number +26660320000
        // Change to a valid Indonesian test number
        DB::table('guests')
            ->where('id', 4)
            ->update([
                'phone' => '+62 812 3456 7890', // Valid Indonesian mobile test number
                'updated_at' => now(),
            ]);

        // Log the change
        \Log::info('Migration: Fixed invalid phone number for guest ID 4', [
            'old_phone' => '+26660320000',
            'new_phone' => '+62 812 3456 7890',
        ]);

        // Optional: Fix any other invalid phone numbers
        // You can uncomment and modify these as needed:

        // // Fix all phone numbers starting with +2666 (invalid country code)
        // DB::table('guests')
        //     ->where('phone', 'like', '+2666%')
        //     ->update([
        //         'phone' => DB::raw("CONCAT('+62 ', SUBSTRING(phone, 6))"),
        //         'updated_at' => now(),
        //     ]);

        // // Remove any phone numbers that are clearly invalid (less than 10 digits)
        // DB::table('guests')
        //     ->whereNotNull('phone')
        //     ->whereRaw('LENGTH(REGEXP_REPLACE(phone, "[^0-9]", "")) < 10')
        //     ->update([
        //         'phone' => null,
        //         'updated_at' => now(),
        //     ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the original phone number (if needed for rollback)
        DB::table('guests')
            ->where('id', 4)
            ->update([
                'phone' => '+26660320000', // Original invalid number
                'updated_at' => now(),
            ]);

        \Log::info('Migration: Rolled back phone number fix for guest ID 4');
    }
};
