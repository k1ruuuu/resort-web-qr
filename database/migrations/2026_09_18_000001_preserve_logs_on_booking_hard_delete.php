<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. qr_scan_logs: Add snapshot columns
        Schema::table('qr_scan_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('qr_scan_logs', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('guest_voucher_id');
            }
            if (!Schema::hasColumn('qr_scan_logs', 'room_name')) {
                $table->string('room_name')->nullable()->after('guest_name');
            }
            if (!Schema::hasColumn('qr_scan_logs', 'booking_code')) {
                $table->string('booking_code')->nullable()->after('room_name');
            }
        });

        // 2. delivery_logs: Add snapshot columns & relax foreign keys to nullOnDelete
        Schema::table('delivery_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_logs', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('guest_voucher_id');
            }
            if (!Schema::hasColumn('delivery_logs', 'booking_code')) {
                $table->string('booking_code')->nullable()->after('guest_name');
            }

            // Ensure foreign keys are nullOnDelete
            try {
                $table->dropForeign(['booking_id']);
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign(['guest_id']);
            } catch (\Throwable $e) {}

            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
            $table->foreign('guest_id')->references('id')->on('guests')->nullOnDelete();
        });

        // 3. redemption_logs: Add snapshot columns & relax foreign keys to nullOnDelete
        Schema::table('redemption_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('redemption_logs', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('guest_id');
            }
            if (!Schema::hasColumn('redemption_logs', 'room_name')) {
                $table->string('room_name')->nullable()->after('guest_name');
            }
            if (!Schema::hasColumn('redemption_logs', 'booking_code')) {
                $table->string('booking_code')->nullable()->after('room_name');
            }
            if (!Schema::hasColumn('redemption_logs', 'property_id')) {
                $table->foreignId('property_id')->nullable()->after('booking_code')->constrained('properties')->nullOnDelete();
            }

            try {
                $table->dropForeign(['guest_voucher_id']);
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign(['booking_id']);
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign(['guest_id']);
            } catch (\Throwable $e) {}

            $table->foreignId('guest_voucher_id')->nullable()->change();

            $table->foreign('guest_voucher_id')->references('id')->on('guest_vouchers')->nullOnDelete();
            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
            $table->foreign('guest_id')->references('id')->on('guests')->nullOnDelete();
        });

        // 4. Backfill existing historical records
        DB::statement("
            UPDATE qr_scan_logs q
            LEFT JOIN guest_vouchers gv ON gv.id = q.guest_voucher_id
            LEFT JOIN bookings b ON b.id = gv.booking_id
            LEFT JOIN guests g ON g.id = COALESCE(gv.guest_id, b.guest_id)
            LEFT JOIN rooms r ON r.id = b.room_id
            SET 
                q.guest_name = COALESCE(NULLIF(TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))), ''), gv.guest_name),
                q.room_name = COALESCE(b.room_label, r.number, r.label, IF(gv.category = 'temporary', 'Temporary', NULL)),
                q.booking_code = COALESCE(b.booking_code, b.reference)
            WHERE q.guest_name IS NULL
        ");

        DB::statement("
            UPDATE delivery_logs d
            LEFT JOIN bookings b ON b.id = d.booking_id
            LEFT JOIN guests g ON g.id = COALESCE(d.guest_id, b.guest_id)
            LEFT JOIN guest_vouchers gv ON gv.id = d.guest_voucher_id
            SET 
                d.guest_name = COALESCE(NULLIF(TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))), ''), gv.guest_name),
                d.booking_code = COALESCE(b.booking_code, b.reference)
            WHERE d.guest_name IS NULL
        ");

        DB::statement("
            UPDATE redemption_logs rl
            LEFT JOIN guest_vouchers gv ON gv.id = rl.guest_voucher_id
            LEFT JOIN bookings b ON b.id = COALESCE(rl.booking_id, gv.booking_id)
            LEFT JOIN guests g ON g.id = COALESCE(rl.guest_id, b.guest_id, gv.guest_id)
            LEFT JOIN rooms r ON r.id = b.room_id
            SET 
                rl.guest_name = COALESCE(NULLIF(TRIM(CONCAT(COALESCE(g.first_name, ''), ' ', COALESCE(g.last_name, ''))), ''), gv.guest_name),
                rl.room_name = COALESCE(b.room_label, r.number, r.label, IF(gv.category = 'temporary', 'Temporary', NULL)),
                rl.booking_code = COALESCE(b.booking_code, b.reference),
                rl.property_id = COALESCE(b.property_id, gv.property_id)
            WHERE rl.guest_name IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('redemption_logs', function (Blueprint $table) {
            try {
                $table->dropForeign(['property_id']);
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign(['guest_voucher_id']);
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign(['booking_id']);
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign(['guest_id']);
            } catch (\Throwable $e) {}

            $table->dropColumn(['guest_name', 'room_name', 'booking_code', 'property_id']);

            $table->foreignId('guest_voucher_id')->nullable(false)->change();

            $table->foreign('guest_voucher_id')->references('id')->on('guest_vouchers')->cascadeOnDelete();
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('guest_id')->references('id')->on('guests')->cascadeOnDelete();
        });

        Schema::table('delivery_logs', function (Blueprint $table) {
            try {
                $table->dropForeign(['booking_id']);
            } catch (\Throwable $e) {}
            try {
                $table->dropForeign(['guest_id']);
            } catch (\Throwable $e) {}

            $table->dropColumn(['guest_name', 'booking_code']);

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('guest_id')->references('id')->on('guests')->cascadeOnDelete();
        });

        Schema::table('qr_scan_logs', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'room_name', 'booking_code']);
        });
    }
};
