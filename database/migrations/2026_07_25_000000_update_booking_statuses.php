<?php

use App\Enums\BookingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'pending' => 'expected_arrival',
            'confirmed_reservation' => 'expected_arrival',
            'checked_in' => 'check_in',
            'checked_out' => 'expected_departure',
        ];

        foreach ($map as $old => $new) {
            DB::table('bookings')
                ->where('status', $old)
                ->update(['status' => $new]);
        }
    }

    public function down(): void
    {
        $map = [
            'expected_arrival' => 'confirmed_reservation',
            'check_in' => 'checked_in',
            'expected_departure' => 'checked_out',
        ];

        foreach ($map as $new => $old) {
            DB::table('bookings')
                ->where('status', $new)
                ->update(['status' => $old]);
        }
    }
};
