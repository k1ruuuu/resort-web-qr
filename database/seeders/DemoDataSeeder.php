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
            ['code' => 'FID', 'name' => 'Forest Camp Indian Deluxe', 'max' => 2],
            ['code' => 'FIS', 'name' => 'Forest Camp Indian Suite', 'max' => 4],
            ['code' => 'FMS', 'name' => 'Forest Camp Monggolian Suite', 'max' => 4],
            ['code' => 'FMF', 'name' => 'Forest Camp Mongolian Family (8 & 9)', 'max' => 9],
            ['code' => 'THC', 'name' => 'Tree House Couple', 'max' => 2],
            ['code' => 'THF', 'name' => 'Tree House Family', 'max' => 4],
            ['code' => 'STH', 'name' => 'Safari Tree House', 'max' => 2],
            ['code' => 'STHF', 'name' => 'Safari Tree House Family (1 & 2)', 'max' => 4],
            ['code' => 'FTJ', 'name' => 'Forest Tent Japan', 'max' => 2],
            ['code' => 'FTF', 'name' => 'Forest Tent Family', 'max' => 4],
            ['code' => 'FC', 'name' => 'Forest Cabin', 'max' => 2],
        ];

        foreach ($types as $typeData) {
            RoomType::query()->updateOrCreate(
                ['property_id' => $property->id, 'code' => $typeData['code']],
                ['name' => $typeData['name'], 'max_occupancy' => $typeData['max']]
            );
        }

        $rooms = [
            // FID
            ['code' => 'FID 01', 'label' => 'FID 01 - Forest Camp Indian Deluxe', 'area' => 'CAMP', 'type' => 'FID', 'capacity' => 2, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'FID 02', 'label' => 'FID 02 - Forest Camp Indian Deluxe', 'area' => 'CAMP', 'type' => 'FID', 'capacity' => 2, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'FID 03', 'label' => 'FID 03 - Forest Camp Indian Deluxe', 'area' => 'CAMP', 'type' => 'FID', 'capacity' => 2, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            // FIS
            ['code' => 'FIS 01', 'label' => 'FIS 01 - Forest Camp Indian Suite', 'area' => 'CAMP', 'type' => 'FIS', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'FIS 02', 'label' => 'FIS 02 - Forest Camp Indian Suite', 'area' => 'CAMP', 'type' => 'FIS', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            // FMS
            ['code' => 'FMS 01', 'label' => 'FMS 01 - Forest Camp Monggolian Suite', 'area' => 'CAMP', 'type' => 'FMS', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'FMS 02', 'label' => 'FMS 02 - Forest Camp Monggolian Suite', 'area' => 'CAMP', 'type' => 'FMS', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'FMS 03', 'label' => 'FMS 03 - Forest Camp Monggolian Suite', 'area' => 'CAMP', 'type' => 'FMS', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'FMS 04', 'label' => 'FMS 04 - Forest Camp Monggolian Suite', 'area' => 'CAMP', 'type' => 'FMS', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'FMS 05', 'label' => 'FMS 05 - Forest Camp Monggolian Suite', 'area' => 'CAMP', 'type' => 'FMS', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'FMS 06', 'label' => 'FMS 06 - Forest Camp Monggolian Suite', 'area' => 'CAMP', 'type' => 'FMS', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'FMS 07', 'label' => 'FMS 07 - Forest Camp Monggolian Suite', 'area' => 'CAMP', 'type' => 'FMS', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            // FMF
            ['code' => 'FMF 1', 'label' => 'FMF 1 - Forest Camp Mongolian Family (8 & 9)', 'area' => 'CAMP', 'type' => 'FMF', 'capacity' => 9, 'bed' => 'QS', 'view' => 'Forest View', 'loc' => '1'],
            // THC
            ['code' => 'TH 01', 'label' => 'TH 01 - Tree House Couple', 'area' => 'TREE', 'type' => 'THC', 'capacity' => 2, 'bed' => 'KS', 'view' => 'Pool View', 'loc' => '1'],
            ['code' => 'TH 02', 'label' => 'TH 02 - Tree House Couple', 'area' => 'TREE', 'type' => 'THC', 'capacity' => 2, 'bed' => 'KS', 'view' => 'Pool View', 'loc' => '1'],
            // THF
            ['code' => 'TH 03', 'label' => 'TH 03 - Tree House Family', 'area' => 'TREE', 'type' => 'THF', 'capacity' => 4, 'bed' => 'KS', 'view' => 'Pool View', 'loc' => '1'],
            // STH
            ['code' => 'STH 1', 'label' => 'STH 1 - Safari Tree House', 'area' => 'TREE', 'type' => 'STH', 'capacity' => 2, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'STH 2', 'label' => 'STH 2 - Safari Tree House', 'area' => 'TREE', 'type' => 'STH', 'capacity' => 2, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'STH 3', 'label' => 'STH 3 - Safari Tree House', 'area' => 'TREE', 'type' => 'STH', 'capacity' => 2, 'bed' => 'KS', 'view' => 'Forest View', 'loc' => '1'],
            // FTJ
            ['code' => 'J 01', 'label' => 'J 01 - Forest Tent Japan', 'area' => 'TENT', 'type' => 'FTJ', 'capacity' => 2, 'bed' => 'QS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'J 02', 'label' => 'J 02 - Forest Tent Japan', 'area' => 'TENT', 'type' => 'FTJ', 'capacity' => 2, 'bed' => 'QS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'J 03', 'label' => 'J 03 - Forest Tent Japan', 'area' => 'TENT', 'type' => 'FTJ', 'capacity' => 2, 'bed' => 'QS', 'view' => 'Forest View', 'loc' => '1'],
            // FTF
            ['code' => 'F 01', 'label' => 'F 01 - Forest Tent Family', 'area' => 'TENT', 'type' => 'FTF', 'capacity' => 4, 'bed' => 'QS', 'view' => 'Forest View', 'loc' => '1'],
            ['code' => 'F 02', 'label' => 'F 02 - Forest Tent Family', 'area' => 'TENT', 'type' => 'FTF', 'capacity' => 4, 'bed' => 'QS', 'view' => 'Forest View', 'loc' => '1'],
            // FC
            ['code' => 'FC 1', 'label' => 'FC 1 - Forest Cabin', 'area' => 'CAMP', 'type' => 'FC', 'capacity' => 2, 'bed' => 'QS', 'view' => 'Forest View', 'loc' => '1'],
        ];

        foreach ($rooms as $roomData) {
            $area = Area::query()->where('property_id', $property->id)->where('code', $roomData['area'])->first();
            $type = RoomType::query()->where('property_id', $property->id)->where('code', $roomData['type'])->first();

            Room::query()->updateOrCreate(
                ['property_id' => $property->id, 'code' => $roomData['code']],
                [
                    'area_id' => $area?->id,
                    'room_type_id' => $type?->id,
                    'number' => $roomData['code'],
                    'label' => $roomData['label'],
                    'capacity' => $roomData['capacity'],
                    'bed_type' => $roomData['bed'],
                    'room_view' => $roomData['view'],
                    'location' => $roomData['loc'],
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

            $outlet = Outlet::query()->firstOrCreate(
                ['property_id' => $property->id, 'code' => $facility['code'].'-OUT'],
                ['name' => $facility['name'].' Counter', 'is_active' => true]
            );
            $outlet->facilityTemplates()->syncWithoutDetaching([$template->id]);
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
            $existing = Booking::where('booking_code', $row['Kode Booking'])->first();
            if ($existing) {
                continue;
            }
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
                $outletBreakfast = $breakfast->outlets()->first();
                $outletTea = $tea->outlets()->first();
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
