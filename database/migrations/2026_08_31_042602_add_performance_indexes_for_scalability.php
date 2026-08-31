<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('qr_scan_logs', function (Blueprint $table) {
            $table->index('scanned_at');
            $table->index('scan_result');
        });

        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->index('qr_code');
            $table->index('status');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['check_in', 'check_out']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qr_scan_logs', function (Blueprint $table) {
            $table->dropIndex(['scanned_at']);
            $table->dropIndex(['scan_result']);
        });

        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->dropIndex(['qr_code']);
            $table->dropIndex(['status']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['check_in', 'check_out']);
        });
    }
};
