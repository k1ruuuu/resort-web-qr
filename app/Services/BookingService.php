<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\VoucherStatus;
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
        private readonly RedisLockService $locks,
        private readonly RedisCacheService $cache,
    ) {}

    public function create(array $data, array $facilities = []): Booking
    {
        return DB::transaction(function () use ($data, $facilities) {
            $data['status'] = BookingStatus::ExpectedArrival;
            $data = $this->enrichBookingData($data);

            $this->assertRoomAvailable($data, null);

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
                    'quota_total' => $quotaTotal,
                ]);
            }

            $this->audit->log('booking.created', $booking, null, $booking->toArray());

            return $booking->load(['guest', 'property', 'room.roomType', 'bookingFacilities.facilityTemplate']);
        });
    }

    public function updateBooking(Booking $booking, array $data, array $facilities = []): Booking
    {
        return DB::transaction(function () use ($booking, $data, $facilities) {
            $old = $booking->toArray();

            $data = $this->enrichBookingData($data);

            $this->assertRoomAvailable($data, $booking->id);

            $booking->update($data);

            if (! empty($facilities)) {
                $booking->bookingFacilities()->delete();
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
                        'quota_total' => $quotaTotal,
                    ]);
                }
            } elseif (
                isset($data['adults']) || isset($data['children']) || isset($data['extra_beds'])
            ) {
                // M-13: pax changed without touching facilities -> keep quotas in sync
                $newQuota = $this->quota->quotaForBooking($booking);
                $booking->bookingFacilities()
                    ->where('quota_total', '!=', $newQuota)
                    ->update(['quota_total' => $newQuota]);
            }

            $this->audit->log('booking.updated', $booking, $old, $booking->toArray());
            $this->cache->invalidateBooking($booking);

            return $booking->fresh(['guest', 'property', 'room.roomType', 'bookingFacilities.facilityTemplate']);
        });
    }

    public function checkIn(Booking $booking, array $facilityTemplateIds = []): Booking
    {
        if ($booking->status === BookingStatus::CheckIn) {
            return $booking;
        }

        // Use distributed lock to prevent double check-in
        $lock = $this->locks->lockBookingCheckIn($booking->id, 15);
        
        if (!$lock) {
            throw new \RuntimeException('Another check-in is in progress for this booking. Please wait.');
        }

        try {
            // Re-verify status AFTER acquiring the lock to prevent TOCTOU
            $booking = $booking->fresh();

            if ($booking->status === BookingStatus::CheckIn) {
                return $booking;
            }

            $old = $booking->only(['status', 'checked_in_at']);

            // Status change, facility sync and voucher generation must be atomic:
            // if voucher generation fails, the booking must NOT stay checked in.
            DB::transaction(function () use ($booking, $facilityTemplateIds) {
                if (!empty($facilityTemplateIds)) {
                    $validIds = FacilityTemplate::query()
                        ->where('property_id', $booking->property_id)
                        ->where('is_active', true)
                        ->whereIn('id', $facilityTemplateIds)
                        ->pluck('id')
                        ->all();

                    if (count($validIds) !== count(array_unique($facilityTemplateIds))) {
                        throw new \InvalidArgumentException('One or more selected facilities are not available for this property.');
                    }

                    $booking->bookingFacilities()->delete();
                    $quotaTotal = $this->quota->quotaForBooking($booking);

                    foreach ($validIds as $facilityTemplateId) {
                        $booking->bookingFacilities()->create([
                            'facility_template_id' => $facilityTemplateId,
                            'start_date' => $booking->check_in,
                            'end_date' => $booking->check_out,
                            'quota_total' => $quotaTotal,
                        ]);
                    }
                } else {
                    $this->syncDefaultFacilities($booking);
                }

                $booking->status = BookingStatus::CheckIn;
                $booking->checked_in_at = now();
                $booking->save();

                app(VoucherService::class)->generateForBooking($booking);
            });

            $this->audit->log('booking.checked_in', $booking, $old, $booking->only(['status', 'checked_in_at']));

            // Cache the booking data
            $this->cache->cacheBooking($booking);
        } finally {
            $lock->release();
        }

        // M-11: Run deliveries OUTSIDE the lock so slow provider HTTP calls
        // do not block concurrent check-ins of other bookings.
        $autoEnabled = \App\Models\Setting::get('delivery.automatic_enabled', '1') === '1';
        $schedEnabled = \App\Models\Setting::get('delivery.scheduled_enabled', '0') === '1';

        // Both can be enabled simultaneously
        if ($autoEnabled) {
            try {
                app(\App\Services\VoucherDeliveryService::class)->sendImmediate($booking);
            } catch (\Throwable $e) {
                \Log::error('Automatic delivery failed on check-in', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($schedEnabled) {
            try {
                app(\App\Services\VoucherDeliveryService::class)->schedule($booking);
            } catch (\Throwable $e) {
                \Log::error('Scheduled delivery failed on check-in', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $booking->fresh(['bookingFacilities.facilityTemplate', 'room.roomType', 'guestVoucher']);
    }

    public function checkOut(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::CheckIn) {
            throw new \RuntimeException('Only checked-in bookings can be checked out.');
        }

        $old = $booking->only(['status', 'checked_out_at']);
        $auditData = $booking->toArray();

        // M-10: Archive instead of delete — keep redemption/scan/delivery history and
        // booking_facilities so reports and exports retain checked-out guests.
        DB::transaction(function () use ($booking) {
            $booking->status = BookingStatus::ExpectedDeparture;
            $booking->checked_out_at = now();
            $booking->save();

            if ($booking->guestVoucher) {
                $booking->guestVoucher->update(['status' => VoucherStatus::Expired]);
            }

            $this->cache->invalidateBooking($booking);
        });

        $this->audit->log('booking.expected_departure', $booking, $old, $auditData);

        return $booking->fresh(['guest', 'property', 'room', 'bookingFacilities.facilityTemplate', 'guestVoucher']);
    }

    /**
     * M-18: prevent overlapping bookings on the same room (excludes archived/cancelled).
     */
    private function assertRoomAvailable(array $data, ?int $ignoreBookingId): void
    {
        if (empty($data['room_id']) || empty($data['check_in']) || empty($data['check_out'])) {
            return;
        }

        $overlap = Booking::query()
            ->where('room_id', $data['room_id'])
            ->whereIn('status', [
                BookingStatus::ExpectedArrival,
                BookingStatus::CheckIn,
            ])
            ->where('check_in', '<', $data['check_out'])
            ->where('check_out', '>', $data['check_in']);

        if ($ignoreBookingId !== null) {
            $overlap->where('id', '!=', $ignoreBookingId);
        }

        if ($overlap->exists()) {
            throw new \InvalidArgumentException('This room is already booked for the selected dates.');
        }
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

        if (array_key_exists('adults', $data)) {
            $data['total_pax'] = ($data['adults'] ?? 1) + ($data['children'] ?? 0);
        }

        if (! empty($data['room_id']) && empty($data['room_label'])) {
            $room = Room::query()->with('roomType')->find($data['room_id']);
            if ($room) {
                $data['room_label'] = $room->label ?? $room->number;
            }
        }

        if (! empty($data['check_in']) && ! empty($data['check_out'])) {
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);
            $data['nights'] = max(1, $checkIn->copy()->startOfDay()->diffInDays($checkOut->copy()->startOfDay()));
            $data['expected_arrival'] ??= $data['check_in'];
            $data['expected_departure'] ??= $data['check_out'];
        }

        return $data;
    }
}
