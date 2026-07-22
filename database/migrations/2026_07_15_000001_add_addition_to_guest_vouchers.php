<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->integer('addition')->nullable()->default(0)->after('pax_limit');
        });
    }

    public function down(): void
    {
        Schema::table('guest_vouchers', function (Blueprint $table) {
            $table->dropColumn('addition');
        });
    }
};
