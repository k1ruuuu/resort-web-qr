<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 32)->unique();
            $table->string('timezone')->default('UTC');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->timestamps();
            $table->unique(['property_id', 'code']);
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->unsignedSmallInteger('max_occupancy')->default(2);
            $table->timestamps();
            $table->unique(['property_id', 'code']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('number', 32);
            $table->string('status', 50)->default('available');
            $table->timestamps();
            $table->unique(['property_id', 'number']);
        });

        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('document_id', 64)->nullable();
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 32)->unique();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
            $table->unsignedSmallInteger('total_pax');
            $table->string('status', 50)->default('pending');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
            $table->index(['property_id', 'status']);
            $table->index(['check_in', 'check_out']);
        });

        Schema::create('facility_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['property_id', 'code']);
        });

        Schema::create('booking_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_template_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('quota_total');
            $table->timestamps();
            $table->unique(['booking_id', 'facility_template_id', 'start_date'], 'booking_facility_unique');
        });

        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_template_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['property_id', 'code']);
        });

        Schema::create('daily_vouchers', function (Blueprint $table) {
            $table->id();
            $table->uuid('qr_token')->unique();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_template_id')->constrained()->cascadeOnDelete();
            $table->date('valid_date');
            $table->unsignedSmallInteger('quota_total');
            $table->unsignedSmallInteger('quota_remaining');
            $table->string('status', 50)->default('active');
            $table->timestamp('generated_at');
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('redeemed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('redeemed_at_outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->timestamps();
            $table->unique(['booking_facility_id', 'valid_date'], 'daily_voucher_facility_date_unique');
            $table->index(['qr_token', 'status']);
        });

        Schema::create('voucher_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 32);
            $table->unsignedSmallInteger('pax_used')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('qr_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('qr_token')->nullable();
            $table->foreignId('daily_voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scan_result', 32);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index('qr_token');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('qr_scan_logs');
        Schema::dropIfExists('voucher_usage_logs');
        Schema::dropIfExists('daily_vouchers');
        Schema::dropIfExists('outlets');
        Schema::dropIfExists('booking_facilities');
        Schema::dropIfExists('facility_templates');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('guests');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('properties');
    }
};
