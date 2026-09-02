<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_scan_logs', function (Blueprint $table) {
            $table->unsignedInteger('pax_used')
                ->nullable()
                ->after('facility_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('qr_scan_logs', function (Blueprint $table) {
            $table->dropColumn('pax_used');
        });
    }
};
