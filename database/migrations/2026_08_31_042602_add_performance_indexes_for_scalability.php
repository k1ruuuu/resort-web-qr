<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEXES FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('qr_scan_logs', function (Blueprint $table) {
            if (!$this->hasIndex('qr_scan_logs', 'qr_scan_logs_scanned_at_index')) {
                $table->index('scanned_at');
            }
            if (!$this->hasIndex('qr_scan_logs', 'qr_scan_logs_scan_result_index')) {
                $table->index('scan_result');
            }
        });

        Schema::table('guest_vouchers', function (Blueprint $table) {
            if (!$this->hasIndex('guest_vouchers', 'guest_vouchers_qr_code_index')) {
                $table->index('qr_code');
            }
            if (!$this->hasIndex('guest_vouchers', 'guest_vouchers_status_index')) {
                $table->index('status');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!$this->hasIndex('bookings', 'bookings_check_in_check_out_index')) {
                $table->index(['check_in', 'check_out']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qr_scan_logs', function (Blueprint $table) {
            if ($this->hasIndex('qr_scan_logs', 'qr_scan_logs_scanned_at_index')) {
                $table->dropIndex(['scanned_at']);
            }
            if ($this->hasIndex('qr_scan_logs', 'qr_scan_logs_scan_result_index')) {
                $table->dropIndex(['scan_result']);
            }
        });

        Schema::table('guest_vouchers', function (Blueprint $table) {
            if ($this->hasIndex('guest_vouchers', 'guest_vouchers_qr_code_index')) {
                $table->dropIndex(['qr_code']);
            }
            if ($this->hasIndex('guest_vouchers', 'guest_vouchers_status_index')) {
                $table->dropIndex(['status']);
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if ($this->hasIndex('bookings', 'bookings_check_in_check_out_index')) {
                $table->dropIndex(['check_in', 'check_out']);
            }
        });
    }
};
