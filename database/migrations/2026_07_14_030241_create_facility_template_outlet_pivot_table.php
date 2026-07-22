<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_template_outlet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['facility_template_id', 'outlet_id'], 'ft_outlet_unique');
        });

        // Migrate existing one-to-many data into the pivot
        DB::table('outlets')
            ->whereNotNull('facility_template_id')
            ->orderBy('id')
            ->each(function ($outlet) {
                DB::table('facility_template_outlet')->insert([
                    'facility_template_id' => $outlet->facility_template_id,
                    'outlet_id' => $outlet->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        // Drop FK and column from outlets
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropForeign(['facility_template_id']);
            $table->dropColumn('facility_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->foreignId('facility_template_id')->nullable()->after('property_id')->constrained()->cascadeOnDelete();
        });

        // Restore data from pivot (pick first facility per outlet)
        DB::table('facility_template_outlet')
            ->orderBy('id')
            ->each(function ($pivot) {
                DB::table('outlets')
                    ->where('id', $pivot->outlet_id)
                    ->whereNull('facility_template_id')
                    ->update(['facility_template_id' => $pivot->facility_template_id]);
            });

        Schema::dropIfExists('facility_template_outlet');
    }
};
