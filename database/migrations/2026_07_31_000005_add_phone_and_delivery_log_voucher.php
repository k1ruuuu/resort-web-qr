<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('guest_name');
        });

        Schema::table('delivery_logs', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['guest_id']);

            $table->foreignId('booking_id')->nullable()->change();
            $table->foreignId('guest_id')->nullable()->change();

            $table->foreignId('guest_voucher_id')->nullable()->after('booking_id')
                ->constrained('guest_vouchers')
                ->nullOnDelete();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('guest_id')->references('id')->on('guests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_logs', function (Blueprint $table) {
            $table->dropForeign(['guest_voucher_id']);
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['guest_id']);

            $table->dropColumn('guest_voucher_id');

            $table->foreignId('booking_id')->nullable(false)->change();
            $table->foreignId('guest_id')->nullable(false)->change();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('guest_id')->references('id')->on('guests')->cascadeOnDelete();
        });

        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
