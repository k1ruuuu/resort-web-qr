<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('guest_vouchers', 'facility_template_id')) {
                $table->foreignId('facility_template_id')->nullable()->after('property_id')->constrained('facility_templates')->nullOnDelete();
            }

            if (!Schema::hasColumn('guest_vouchers', 'pax_limit')) {
                $table->unsignedInteger('pax_limit')->nullable()->after('facility_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('guest_vouchers', 'pax_limit')) {
                $table->dropColumn('pax_limit');
            }

            if (Schema::hasColumn('guest_vouchers', 'facility_template_id')) {
                $table->dropConstrainedForeignId('facility_template_id');
            }
        });
    }
};
