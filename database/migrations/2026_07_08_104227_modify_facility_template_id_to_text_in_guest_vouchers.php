<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the foreign key constraint first
        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->dropForeign(['facility_template_id']);
        });

        // Modify the column to TEXT type to store comma-separated IDs
        DB::statement('ALTER TABLE guest_vouchers MODIFY facility_template_id TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to BIGINT and re-add foreign key
        DB::statement('ALTER TABLE guest_vouchers MODIFY facility_template_id BIGINT UNSIGNED NULL');
        
        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->foreign('facility_template_id')->references('id')->on('facility_templates')->nullOnDelete();
        });
    }
};
