<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('guest_vouchers', 'property_id')) {
            Schema::table('guest_vouchers', function (Blueprint $table) {
                $table->foreignId('property_id')->nullable()->after('guest_id')->constrained()->nullOnDelete();
            });
        }
        
        if (!Schema::hasColumn('guest_vouchers', 'category')) {
            Schema::table('guest_vouchers', function (Blueprint $table) {
                $table->string('category', 50)->default('standard')->after('status');
            });
        }
        
        if (!Schema::hasColumn('guest_vouchers', 'guest_name')) {
            Schema::table('guest_vouchers', function (Blueprint $table) {
                $table->string('guest_name', 255)->nullable()->after('guest_id');
            });
        }
        
        if (!Schema::hasColumn('guest_vouchers', 'expires_at')) {
            Schema::table('guest_vouchers', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('generated_at');
            });
        }

        DB::statement('ALTER TABLE guest_vouchers MODIFY booking_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE guest_vouchers MODIFY guest_id BIGINT UNSIGNED NULL');

        Schema::table('redemption_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->change();
            $table->unsignedBigInteger('guest_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('redemption_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable(false)->change();
            $table->unsignedBigInteger('guest_id')->nullable(false)->change();
        });

        DB::statement('ALTER TABLE guest_vouchers MODIFY booking_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE guest_vouchers MODIFY guest_id BIGINT UNSIGNED NOT NULL');

        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->dropColumn(['category', 'guest_name', 'expires_at']);
        });
    }
};
