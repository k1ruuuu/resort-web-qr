<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Area;
use App\Models\Booking;
use App\Models\BookingFacility;
use App\Models\FacilityTemplate;
use App\Models\Guest;
use App\Models\Outlet;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\PmsBookingImportService;
use App\Services\StayQuotaService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $property = Property::query()->firstOrCreate(
            ['code' => 'CHANAYA'],
            [
                'name' => 'Chanaya Resort Village',
                'timezone' => 'Asia/Jakarta',
                'address' => 'Chanaya Resort',
                'is_active' => true,
            ]
        );

        $areas = [
            ['code' => 'TREE', 'name' => 'Treehouse'],
            ['code' => 'TENT', 'name' => 'Forest Tent'],
            ['code' => 'CAMP', 'name' => 'Camping Ground'],
        ];

        foreach ($areas as $index => $areaData) {
            Area::query()->firstOrCreate(
                ['property_id' => $property->id, 'code' => $areaData['code']],
                ['name' => $areaData['name']]
            );
        }

        $types = [
            ['code' => 'FAMILY', 'name' => 'Family', 'max' => 4],
            ['code' => 'COUPLE', 'name' => 'Couple', 'max' => 2],
            ['code' => 'DELUXE', 'name' => 'Deluxe', 'max' => 3],
            ['code' => 'SUITE', 'name' => 'Suite', 'max' => 4],
        ];

        foreach ($types as $typeData) {
            RoomType::query()->firstOrCreate(
                ['property_id' => $property->id, 'code' => $typeData['code']],
                ['name' => $typeData['name'], 'max_occupancy' => $typeData['max']]
            );
        }

        $rooms = [
            ['code' => 'FID 02', 'label' => 'FID 02 - Forest Camp Indian Deluxe', 'area' => 'CAMP', 'type' => 'DELUXE', 'capacity' => 2],
            ['code' => 'FMS 02', 'label' => 'FMS 02 - Forest Camp Mongolian Suite', 'area' => 'CAMP', 'type' => 'SUITE', 'capacity' => 2],
            ['code' => 'J 01', 'label' => 'J 01 - Forest Tent Japan', 'area' => 'TENT', 'type' => 'COUPLE', 'capacity' => 2],
            ['code' => 'J 02', 'label' => 'J 02 - Forest Tent Japan', 'area' => 'TENT', 'type' => 'COUPLE', 'capacity' => 2],
        ];

        foreach ($rooms as $roomData) {
            $area = Area::query()->where('property_id', $property->id)->where('code', $roomData['area'])->first();
            $type = RoomType::query()->where('property_id', $property->id)->where('code', $roomData['type'])->first();

            Room::query()->firstOrCreate(
                ['property_id' => $property->id, 'code' => $roomData['code']],
                [
                    'area_id' => $area?->id,
                    'room_type_id' => $type?->id,
                    'number' => $roomData['code'],
                    'label' => $roomData['label'],
                    'capacity' => $roomData['capacity'],
                    'status' => 'available',
                ]
            );
        }

        $facilities = [
            ['code' => 'SNACK', 'name' => 'Welcome Snack', 'order' => 1],
            ['code' => 'TEA', 'name' => 'Afternoon Tea', 'order' => 2],
            ['code' => 'DINNER', 'name' => 'Dinner', 'order' => 3],
            ['code' => 'BREAKFAST', 'name' => 'Breakfast', 'order' => 4],
            ['code' => 'JOURNAL', 'name' => 'Dream Journaling', 'order' => 5],
            ['code' => 'FEED', 'name' => 'Feeding Animal', 'order' => 6],
        ];

        foreach ($facilities as $facility) {
            $template = FacilityTemplate::query()->firstOrCreate(
                ['property_id' => $property->id, 'code' => $facility['code']],
                ['name' => $facility['name'], 'is_active' => true, 'sort_order' => $facility['order']]
            );

            Outlet::query()->firstOrCreate(
                ['property_id' => $property->id, 'code' => $facility['code'].'-OUT'],
                ['facility_template_id' => $template->id, 'name' => $facility['name'].' Counter', 'is_active' => true]
            );
        }

        $this->seedSampleBookings($property);
    }

    private function seedSampleBookings(Property $property): void
    {
        $importer = app(PmsBookingImportService::class);
        $quota = app(StayQuotaService::class);

        $rows = [
            [
                'Kode Booking' => '4005451',
                'Hotel' => 'Chanaya Resort',
                'Nama' => 'muhammad Giri',
                'Kamar' => 'FID 02 - Forest Camp Indian Deluxe',
                'Jumlah' => "1 Malam\n2 Pax",
                'Source' => 'Online Travel Agent',
                'Status Booking' => 'Confirmed Reservation',
                'Expected Arrival' => '01-06-2026',
                'Expected Departure' => '02-06-2026',
                'Voucher' => '50171270',
            ],
            [
                'Kode Booking' => '4005002',
                'Hotel' => 'Chanaya Resort',
                'Nama' => 'Eduarno Saqira',
                'Kamar' => 'J 01 - Forest Tent Japan',
                'Jumlah' => "1 Malam\n2 Pax",
                'Source' => 'RSV by Phone',
                'Status Booking' => 'Check In',
                'Expected Arrival' => \Carbon\Carbon::today()->format('d-m-Y'),
                'Check In' => \Carbon\Carbon::today()->format('d-m-Y').' 11:41:51',
                'Expected Departure' => \Carbon\Carbon::tomorrow()->format('d-m-Y'),
            ],
        ];

        foreach ($rows as $row) {
            $booking = $importer->importRow($property, $row);

            if ($booking->status === BookingStatus::CheckedIn) {
                $templates = FacilityTemplate::query()->where('property_id', $property->id)->where('is_active', true)->get();
                $q = $quota->quotaForBooking($booking);

                foreach ($templates as $template) {
                    BookingFacility::query()->firstOrCreate(
                        [
                            'booking_id' => $booking->id,
                            'facility_template_id' => $template->id,
                            'start_date' => $booking->check_in,
                        ],
                        ['end_date' => $booking->check_out, 'quota_total' => $q]
                    );
                }

                $voucher = app(\App\Services\VoucherService::class)->generateForBooking($booking);

                $breakfast = FacilityTemplate::where('code', 'BREAKFAST')->first();
                $tea = FacilityTemplate::where('code', 'TEA')->first();
                $outletBreakfast = Outlet::where('facility_template_id', $breakfast->id)->first();
                $outletTea = Outlet::where('facility_template_id', $tea->id)->first();
                $adminUser = \App\Models\User::first();

                if ($breakfast && $outletBreakfast && $adminUser) {
                    \App\Models\RedemptionLog::create([
                        'guest_voucher_id' => $voucher->id,
                        'guest_id' => $booking->guest_id,
                        'booking_id' => $booking->id,
                        'facility_template_id' => $breakfast->id,
                        'outlet_id' => $outletBreakfast->id,
                        'user_id' => $adminUser->id,
                        'pax_used' => 2,
                        'remaining_quota' => $q - 2,
                        'date' => \Carbon\Carbon::today()->toDateString(),
                        'time' => '08:30:00',
                        'ip_address' => '127.0.0.1',
                    ]);
                }

                if ($tea && $outletTea && $adminUser) {
                    \App\Models\RedemptionLog::create([
                        'guest_voucher_id' => $voucher->id,
                        'guest_id' => $booking->guest_id,
                        'booking_id' => $booking->id,
                        'facility_template_id' => $tea->id,
                        'outlet_id' => $outletTea->id,
                        'user_id' => $adminUser->id,
                        'pax_used' => 1,
                        'remaining_quota' => $q - 1,
                        'date' => \Carbon\Carbon::today()->toDateString(),
                        'time' => '15:15:00',
                        'ip_address' => '127.0.0.1',
                    ]);
                }
            }
        }
    }
}
