<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qr_scan_logs');
        Schema::dropIfExists('voucher_usage_logs');
        Schema::dropIfExists('daily_vouchers');

        Schema::create('guest_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('qr_code', 255)->unique();
            $table->string('secure_token', 255)->unique();
            $table->string('status', 50)->default('active');
            $table->timestamp('generated_at');
            $table->timestamps();
        });

        Schema::create('redemption_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_voucher_id')->constrained('guest_vouchers')->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('pax_used')->default(1);
            $table->unsignedSmallInteger('remaining_quota')->default(0);
            $table->date('date');
            $table->time('time');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('qr_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code', 255)->nullable();
            $table->string('secure_token', 255)->nullable();
            $table->foreignId('guest_voucher_id')->nullable()->constrained('guest_vouchers')->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scan_result', 32);
            $table->timestamp('scanned_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scan_logs');
        Schema::dropIfExists('redemption_logs');
        Schema::dropIfExists('guest_vouchers');
    }
};
