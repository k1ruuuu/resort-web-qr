<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->after('property_id');
            $table->string('label')->nullable()->after('number');
            $table->unsignedSmallInteger('capacity')->default(2)->after('label');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->string('whatsapp', 32)->nullable()->after('phone');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_code', 32)->nullable()->unique()->after('id');
            $table->string('source', 64)->nullable()->after('reference');
            $table->string('room_label')->nullable()->after('room_id');
            $table->date('expected_arrival')->nullable()->after('room_label');
            $table->date('expected_departure')->nullable()->after('expected_arrival');
            $table->unsignedSmallInteger('nights')->default(1)->after('check_out');
            $table->unsignedSmallInteger('extra_beds')->default(0)->after('children');
            $table->string('pms_voucher_ref', 64)->nullable()->after('status');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
        });

        Schema::table('facility_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
        });

        Schema::table('daily_vouchers', function (Blueprint $table) {
            $table->string('qr_code', 255)->nullable()->unique()->after('id');
            $table->uuid('public_token')->nullable()->unique()->after('qr_code');
        });

        Schema::table('qr_scan_logs', function (Blueprint $table) {
            $table->string('qr_code', 255)->nullable()->after('id');
            $table->timestamp('scanned_at')->nullable()->after('scan_result');
        });

        Schema::table('voucher_usage_logs', function (Blueprint $table) {
            $table->timestamp('used_at')->nullable()->after('pax_used');
        });
    }

    public function down(): void
    {
        Schema::table('voucher_usage_logs', function (Blueprint $table) {
            $table->dropColumn('used_at');
        });

        Schema::table('qr_scan_logs', function (Blueprint $table) {
            $table->dropColumn(['qr_code', 'scanned_at']);
        });

        Schema::table('daily_vouchers', function (Blueprint $table) {
            $table->dropColumn(['qr_code', 'public_token']);
        });

        Schema::table('facility_templates', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'booking_code', 'source', 'room_label', 'expected_arrival',
                'expected_departure', 'nights', 'extra_beds', 'pms_voucher_ref', 'checked_out_at',
            ]);
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('whatsapp');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['code', 'label', 'capacity']);
        });
    }
};
