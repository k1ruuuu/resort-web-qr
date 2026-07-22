<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('guest_vouchers', 'addition_facility_id')) {
                $table->dropForeign(['addition_facility_id']);
                $table->dropColumn('addition_facility_id');
            }
            if (!Schema::hasColumn('guest_vouchers', 'addition_facility_ids')) {
                $table->text('addition_facility_ids')->nullable()->after('addition');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('guest_vouchers', 'addition_facility_ids')) {
                $table->dropColumn('addition_facility_ids');
            }
            if (!Schema::hasColumn('guest_vouchers', 'addition_facility_id')) {
                $table->foreignId('addition_facility_id')->nullable()->after('addition')
                    ->constrained('facility_templates')->nullOnDelete();
            }
        });
    }
};
