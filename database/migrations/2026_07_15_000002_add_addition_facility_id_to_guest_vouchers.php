<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->foreignId('addition_facility_id')->nullable()->after('addition')
                ->constrained('facility_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('addition_facility_id');
        });
    }
};
