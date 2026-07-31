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
        // Drop the foreign key constraint first if it exists.
        $foreignKey = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'guest_vouchers' AND COLUMN_NAME = 'facility_template_id' AND REFERENCED_TABLE_NAME = 'facility_templates'"
        );

        if ($foreignKey && isset($foreignKey->CONSTRAINT_NAME)) {
            Schema::table('guest_vouchers', function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey->CONSTRAINT_NAME);
            });
        }

        // Drop any index on facility_template_id before converting the column to TEXT.
        $indexes = DB::select(
            "SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'guest_vouchers' AND COLUMN_NAME = 'facility_template_id'"
        );

        foreach ($indexes as $index) {
            if (!empty($index->INDEX_NAME) && strtoupper($index->INDEX_NAME) !== 'PRIMARY') {
                DB::statement(sprintf('ALTER TABLE guest_vouchers DROP INDEX `%s`', $index->INDEX_NAME));
            }
        }

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
