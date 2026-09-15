<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_facility_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_voucher_id')->constrained('guest_vouchers')->cascadeOnDelete();
            $table->date('exchange_date');
            $table->foreignId('from_facility_template_id')->constrained('facility_templates')->cascadeOnDelete();
            $table->foreignId('to_facility_template_id')->constrained('facility_templates')->cascadeOnDelete();
            $table->unsignedInteger('pax');
            $table->string('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['guest_voucher_id', 'exchange_date']);
            $table->index('from_facility_template_id');
            $table->index('to_facility_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_facility_exchanges');
    }
};
