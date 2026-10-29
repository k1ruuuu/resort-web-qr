<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $propertyId = DB::table('properties')->value('id') ?? 1;

        // 1. Add SPA facility template if not exists
        $facilityId = DB::table('facility_templates')->where('code', 'SPA')->value('id');
        if (!$facilityId) {
            $facilityId = DB::table('facility_templates')->insertGetId([
                'property_id' => $propertyId,
                'name' => 'Spa',
                'code' => 'SPA',
                'description' => 'Spa Treatment',
                'is_active' => 1,
                'is_one_time' => null,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Add Spa Counter outlet if not exists
        $outletId = DB::table('outlets')->where('code', 'SPA-OUT')->value('id');
        if (!$outletId) {
            $outletId = DB::table('outlets')->insertGetId([
                'property_id' => $propertyId,
                'name' => 'Spa Counter',
                'code' => 'SPA-OUT',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Attach facility to outlet in pivot table
        $exists = DB::table('facility_template_outlet')
            ->where('facility_template_id', $facilityId)
            ->where('outlet_id', $outletId)
            ->exists();

        if (!$exists) {
            DB::table('facility_template_outlet')->insert([
                'facility_template_id' => $facilityId,
                'outlet_id' => $outletId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $facilityId = DB::table('facility_templates')->where('code', 'SPA')->value('id');
        $outletId = DB::table('outlets')->where('code', 'SPA-OUT')->value('id');

        if ($facilityId && $outletId) {
            DB::table('facility_template_outlet')
                ->where('facility_template_id', $facilityId)
                ->where('outlet_id', $outletId)
                ->delete();
        }

        if ($facilityId) {
            DB::table('facility_templates')->where('id', $facilityId)->delete();
        }

        if ($outletId) {
            DB::table('outlets')->where('id', $outletId)->delete();
        }
    }
};
