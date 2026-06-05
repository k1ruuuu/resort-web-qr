<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingFacility;
use App\Models\FacilityTemplate;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly StayQuotaService $quota,
    ) {}

    public function create(array $data, array $facilities = []): Booking
    {
        return DB::transaction(function () use ($data, $facilities) {
            $data = $this->enrichBookingData($data);

            $booking = Booking::query()->create($data);
            $quotaTotal = $this->quota->quotaForBooking($booking);

            foreach ($facilities as $facility) {
                if (empty($facility['facility_template_id'])) {
                    continue;
                }

                BookingFacility::query()->create([
                    'booking_id' => $booking->id,
                    'facility_template_id' => $facility['facility_template_id'],
                    'start_date' => $facility['start_date'] ?? $booking->check_in,
                    'end_date' => $facility['end_date'] ?? $booking->check_out,
                    'quota_total' => $facility['quota_total'] ?? $quotaTotal,
                ]);
            }

            $this->audit->log('booking.created', $booking, null, $booking->toArray());

            return $booking->load(['guest', 'property', 'room.roomType', 'bookingFacilities.facilityTemplate']);
        });
    }

    public function checkIn(Booking $booking): Booking
    {
        if ($booking->status === BookingStatus::CheckedIn) {
            return $booking;
        }

        $this->syncDefaultFacilities($booking);

        $old = $booking->only(['status', 'checked_in_at']);

        $booking->update([
            'status' => BookingStatus::CheckedIn,
            'checked_in_at' => now(),
        ]);

        app(VoucherService::class)->generateForBooking($booking);

        $autoEnabled = \App\Models\Setting::get('delivery.automatic_enabled', '1') === '1';
        $schedEnabled = \App\Models\Setting::get('delivery.scheduled_enabled', '0') === '1';

        if ($autoEnabled) {
            app(\App\Services\VoucherDeliveryService::class)->sendImmediate($booking);
        } elseif ($schedEnabled) {
            app(\App\Services\VoucherDeliveryService::class)->schedule($booking);
        }

        $this->audit->log('booking.checked_in', $booking, $old, $booking->only(['status', 'checked_in_at']));

        return $booking->fresh(['bookingFacilities.facilityTemplate', 'room.roomType', 'guestVoucher']);
    }

    public function checkOut(Booking $booking): Booking
    {
        $booking->update([
            'status' => BookingStatus::CheckedOut,
            'checked_out_at' => now(),
        ]);

        $this->audit->log('booking.checked_out', $booking);

        return $booking->fresh();
    }

    public function syncDefaultFacilities(Booking $booking): void
    {
        if ($booking->bookingFacilities()->exists()) {
            return;
        }

        $quotaTotal = $this->quota->quotaForBooking($booking);

        $templates = FacilityTemplate::query()
            ->where('property_id', $booking->property_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($templates as $template) {
            BookingFacility::query()->create([
                'booking_id' => $booking->id,
                'facility_template_id' => $template->id,
                'start_date' => $booking->check_in,
                'end_date' => $booking->check_out,
                'quota_total' => $quotaTotal,
            ]);
        }
    }

    private function enrichBookingData(array $data): array
    {
        $data['reference'] ??= strtoupper(Str::random(8));
        $data['total_pax'] ??= ($data['adults'] ?? 1) + ($data['children'] ?? 0);
        $data['status'] ??= BookingStatus::ConfirmedReservation;

        if (! empty($data['room_id']) && empty($data['room_label'])) {
            $room = Room::query()->with('roomType')->find($data['room_id']);
            if ($room) {
                $data['room_label'] = $room->label ?? $room->number;
            }
        }

        if (! empty($data['check_in']) && ! empty($data['check_out'])) {
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);
            $data['nights'] ??= max(1, $checkIn->diffInDays($checkOut));
            $data['expected_arrival'] ??= $data['check_in'];
            $data['expected_departure'] ??= $data['check_out'];
        }

        return $data;
    }
}
