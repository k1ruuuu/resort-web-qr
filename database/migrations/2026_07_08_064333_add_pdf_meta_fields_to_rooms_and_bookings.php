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
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('bed_type', 32)->nullable()->after('capacity');
            $table->string('room_view', 64)->nullable()->after('bed_type');
            $table->string('location', 64)->nullable()->after('room_view');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('arrangement_code', 64)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['bed_type', 'room_view', 'location']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('arrangement_code');
        });
    }
};
