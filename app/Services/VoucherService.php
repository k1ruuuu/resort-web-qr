<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\VoucherException;
use App\Models\Booking;
use App\Models\FacilityTemplate;
use App\Models\GuestVoucher;
use App\Models\Outlet;
use App\Models\Property;
use App\Models\QrScanLog;
use App\Models\RedemptionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly BookingService $bookings,
        private readonly StayQuotaService $quota,
        private readonly RedisLockService $locks,
        private readonly RedisCacheService $cache,
    ) {}

    public function generateForBooking(Booking $booking): GuestVoucher
    {
        if ($booking->status !== BookingStatus::CheckedIn) {
            throw VoucherException::bookingNotCheckedIn();
        }

        return $this->createVoucherForBooking($booking);
    }

    public function generateTemporaryVoucher(array $data): GuestVoucher
    {
        $property = Property::query()->findOrFail($data['property_id']);
        $guestName = trim((string) ($data['guest_name'] ?? 'Temporary Guest'));
        $category = (string) ($data['category'] ?? 'temporary');
        
        // Handle facility selection logic
        $facilitySelection = (string) ($data['facility_selection'] ?? 'single');
        $facilityTemplateIds = [];
        
        if ($facilitySelection === 'all') {
            // Select all active facilities for the property
            $facilityTemplateIds = FacilityTemplate::query()
                ->where('property_id', $property->id)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        } elseif ($facilitySelection === 'multiple' || $facilitySelection === 'single') {
            // Use the provided facility IDs
            $facilityTemplateIds = array_filter(array_map('intval', (array) ($data['facility_template_ids'] ?? [])));
        }
        
        $paxLimit = isset($data['pax_limit']) ? max(1, (int) $data['pax_limit']) : 1;
        $expiresAt = $this->resolveTemporaryExpiry($data, $property);
        $lock = $this->locks->lockVoucherGeneration((int) $property->id, 15);

        if (!$lock) {
            throw new VoucherException('Another voucher generation is in progress. Please wait.', 409);
        }

        try {
            $qrCode = $this->buildQrCode($guestName, 'TEMP', 'TEMP', $expiresAt->format('Y-m-d-H-i'));
            $secureToken = (string) Str::random(32);

            return DB::transaction(function () use ($guestName, $category, $expiresAt, $property, $qrCode, $secureToken, $facilityTemplateIds, $paxLimit) {
                $voucher = GuestVoucher::query()->create([
                    'booking_id' => null,
                    'guest_id' => null,
                    'property_id' => $property->id,
                    'facility_template_id' => $facilityTemplateIds ? implode(',', $facilityTemplateIds) : null,
                    'pax_limit' => $paxLimit,
                    'guest_name' => $guestName,
                    'qr_code' => $qrCode,
                    'secure_token' => $secureToken,
                    'status' => VoucherStatus::Active,
                    'category' => $category,
                    'generated_at' => now(),
                    'expires_at' => $expiresAt,
                ]);

                $this->audit->log('voucher.generated', $voucher, null, $voucher->toArray());

                return $voucher;
            });
        } finally {
            $lock->release();
        }
    }

    public function updateVoucher(GuestVoucher $voucher, array $data): GuestVoucher
    {
        $facilityTemplateIds = [];
        if (isset($data['facility_status']) && is_array($data['facility_status'])) {
            foreach ($data['facility_status'] as $id => $status) {
                if ($status === 'granted') {
                    $facilityTemplateIds[] = (int) $id;
                }
            }
        }
        if (empty($facilityTemplateIds)) {
            throw new VoucherException('At least one facility must be selected for the voucher.', 422);
        }

        $validFacilityCount = FacilityTemplate::query()
            ->where('property_id', $voucher->property_id)
            ->where('is_active', true)
            ->whereIn('id', $facilityTemplateIds)
            ->count();

        if ($validFacilityCount !== count($facilityTemplateIds)) {
            throw new VoucherException('One or more selected facilities are invalid for this voucher.', 422);
        }

        $addition = isset($data['addition'])
            ? max(0, (int) $data['addition'])
            : ($voucher->addition ?? 0);

        $additionFacilityIds = [];
        if ($addition > 0 && !empty($data['addition_facility_ids'])) {
            $additionFacilityIds = array_map('intval', (array) $data['addition_facility_ids']);
        }

        $lock = $this->locks->lockVoucherGeneration($voucher->id, 15);
        if (!$lock) {
            throw new VoucherException('Another voucher update is in progress. Please wait.', 409);
        }

        try {
            return DB::transaction(function () use ($voucher, $facilityTemplateIds, $addition, $additionFacilityIds) {
                $voucher->update([
                    'facility_template_id' => implode(',', $facilityTemplateIds),
                    'addition' => $addition,
                    'addition_facility_ids' => $additionFacilityIds ? implode(',', $additionFacilityIds) : null,
                ]);

                $this->audit->log('voucher.updated', $voucher, null, $voucher->toArray());

                return $voucher->refresh();
            });
        } finally {
            $lock->release();
        }
    }

    private function createVoucherForBooking(Booking $booking): GuestVoucher
    {
        // Use distributed lock to prevent duplicate voucher generation
        $lock = $this->locks->lockVoucherGeneration($booking->id, 15);
        
        if (!$lock) {
            throw new VoucherException('Another voucher generation is in progress for this booking. Please wait.', 409);
        }

        try {
            $booking->loadMissing(['property', 'room.roomType', 'bookingFacilities.facilityTemplate', 'guest']);

            if ($booking->bookingFacilities->isEmpty()) {
                $this->bookings->syncDefaultFacilities($booking);
                $booking->load('bookingFacilities.facilityTemplate');
            }

            if ($booking->bookingFacilities->isEmpty()) {
                throw VoucherException::noFacilities();
            }

            $guestName = $booking->guest->full_name;
            $roomCode = $booking->room?->code ?? $booking->room?->number ?? 'ROOM';
            $roomName = $booking->room?->label ?? $booking->room?->roomType?->name ?? 'Room';
            $date = $booking->check_in->format('Y-m-d');

            $qrCode = $this->buildQrCode($guestName, $roomCode, $roomName, $date);

            $secureToken = (string) Str::random(32);

            $voucher = DB::transaction(function () use ($booking, $qrCode, $secureToken, $guestName) {
                $voucher = GuestVoucher::query()->where('booking_id', $booking->id)->first();

                if (!$voucher) {
                    $voucher = GuestVoucher::query()->create([
                        'booking_id' => $booking->id,
                        'guest_id' => $booking->guest_id,
                        'property_id' => $booking->property_id,
                        'guest_name' => $guestName,
                        'qr_code' => $qrCode,
                        'secure_token' => $secureToken,
                        'status' => VoucherStatus::Active,
                        'category' => 'standard',
                        'generated_at' => now(),
                        'expires_at' => null,
                    ]);

                    $this->audit->log('voucher.generated', $voucher, null, $voucher->toArray());
                }

                return $voucher;
            });

            // Cache the voucher for fast retrieval
            $this->cache->cacheVoucher($voucher);

            return $voucher;
        } finally {
            $lock->release();
        }
    }

    public function redeem(
        string $qrCode,
        Outlet $outlet,
        User $user,
        int $facilityTemplateId,
        int $paxUsed = 1
    ): RedemptionLog {
        $voucher = null;
        $lock = null;

        try {
            // Rate limiting per IP
            if (!$this->cache->trackScan($qrCode, request()->ip())) {
                throw new VoucherException('Too many scan attempts. Please wait a moment.', 429);
            }

            // Try to get voucher from cache first
            $voucher = $this->cache->getVoucherByToken($qrCode);
            
            if (!$voucher) {
                // Fallback to database
                $voucher = GuestVoucher::query()
                    ->where('secure_token', $qrCode)
                    ->orWhere('qr_code', $qrCode)
                    ->first();

                if (!$voucher) {
                    $this->logScan($qrCode, null, $outlet, $user, 'not_found');
                    throw VoucherException::notFound();
                }

                // Cache for next time
                $this->cache->cacheVoucher($voucher);
            }

            // Use distributed lock to prevent double redemption
            $lock = $this->locks->lockVoucherRedemption($voucher->id, 15);
            
            if (!$lock) {
                throw new VoucherException('Another redemption is in progress. Please wait.', 409);
            }

            return DB::transaction(function () use ($qrCode, $voucher, $outlet, $user, $facilityTemplateId, $paxUsed) {
                // Reload with lock to get fresh data
                $voucher = GuestVoucher::query()
                    ->where('id', $voucher->id)
                    ->lockForUpdate()
                    ->first();

                // Auto-expire if passed checkout time
                $this->checkAndExpireIfNeeded($voucher);

                if ($voucher->status !== VoucherStatus::Active) {
                    throw new VoucherException('Voucher is no longer active.', 422);
                }

                if ($voucher->booking && $voucher->booking->status !== BookingStatus::CheckedIn) {
                    throw new VoucherException('Booking is not currently checked in.', 422);
                }

                if ($voucher->category === 'temporary') {
                    $expiresAt = $voucher->expires_at;
                    if (!$expiresAt) {
                        throw new VoucherException('Temporary voucher expiry is not configured.', 422);
                    }

                    $timezone = $voucher->property?->timezone ?? 'UTC';
                    $currentDateTime = Carbon::now($timezone);
                    if ($currentDateTime->gte($expiresAt)) {
                        $voucher->update(['status' => VoucherStatus::Expired]);
                        throw new VoucherException('Temporary voucher has expired.', 422);
                    }

                    if ($voucher->property && $outlet->property_id !== $voucher->property_id) {
                        throw new VoucherException('This outlet belongs to a different property.', 403);
                    }

                    $today = Carbon::today($timezone);
                    $todayString = $today->toDateString();
                    
                    // CRITICAL: Calculate quota from database WITH row-level locking to prevent race conditions
                    $totalUsedToday = DB::table('redemption_logs')
                        ->where('guest_voucher_id', $voucher->id)
                        ->where('facility_template_id', $facilityTemplateId)
                        ->where('date', $todayString)
                        ->lockForUpdate()
                        ->sum('pax_used');
                    
                    $additionIds = $voucher->addition_facility_ids
                        ? array_map('intval', explode(',', $voucher->addition_facility_ids))
                        : [];
                    $basePax = $voucher->pax_limit ?? 1;
                    $paxLimit = $basePax + (in_array($facilityTemplateId, $additionIds) ? ($voucher->addition ?? 0) : 0);
                    $quotaRemaining = max(0, $paxLimit - $totalUsedToday);
                    
                    if ($quotaRemaining <= 0) {
                        throw VoucherException::expired();
                    }

                    if ($paxUsed > $quotaRemaining) {
                        throw VoucherException::quotaExceeded();
                    }

                    $now = now();
                    $remainingQuota = $quotaRemaining - $paxUsed;

                    $log = RedemptionLog::query()->create([
                        'guest_voucher_id' => $voucher->id,
                        'guest_id' => $voucher->guest_id,
                        'booking_id' => $voucher->booking_id,
                        'facility_template_id' => $facilityTemplateId,
                        'outlet_id' => $outlet->id,
                        'user_id' => $user->id,
                        'pax_used' => $paxUsed,
                        'remaining_quota' => $remainingQuota,
                        'date' => $today->toDateString(),
                        'time' => $now->toTimeString(),
                        'ip_address' => request()->ip(),
                    ]);

                    $this->logScan($qrCode, $voucher, $outlet, $user, 'success');
                    $this->audit->log('voucher.redeemed', $voucher, null, $log->toArray());
                    $this->cache->invalidateVoucher($voucher);

                    $this->updateVoucherStatusIfFullyRedeemed($voucher);

                    return $log->load(['guestVoucher', 'guest', 'booking', 'facilityTemplate', 'outlet', 'user']);
                }

                $timezone = $voucher->booking->property->timezone ?? 'UTC';
                $currentDateTime = Carbon::now($timezone);
                $checkInDate = Carbon::parse($voucher->booking->check_in)->setTimezone($timezone)->startOfDay();
                $checkOutDate = Carbon::parse($voucher->booking->check_out)->setTimezone($timezone)->startOfDay();
                
                // Voucher expires at 9 PM (21:00) WIB on checkout date
                $expirationDateTime = $checkOutDate->copy()->setTime(21, 0, 0);

                // Check if before check-in
                if ($currentDateTime->lt($checkInDate)) {
                    throw new VoucherException(
                        'QR code is not yet valid. Valid from: ' . $checkInDate->format('Y-m-d H:i') . ' (' . $timezone . ')',
                        422
                    );
                }

                // Check if after expiration (9 PM on checkout date)
                if ($currentDateTime->gte($expirationDateTime)) {
                    throw new VoucherException(
                        'QR code has expired. It was valid until ' . $expirationDateTime->format('Y-m-d H:i') . ' (' . $timezone . ')',
                        422
                    );
                }

                if ($outlet->property_id !== $voucher->booking->property_id) {
                    throw new VoucherException('This outlet belongs to a different property.', 403);
                }

                $today = Carbon::today($voucher->booking->property->timezone ?? 'UTC');
                $todayString = $today->toDateString();
                
                // CRITICAL: Calculate quota from database WITH row-level locking to prevent race conditions
                // This ensures that concurrent redemptions cannot bypass quota limits
                $totalUsedToday = DB::table('redemption_logs')
                    ->where('guest_voucher_id', $voucher->id)
                    ->where('facility_template_id', $facilityTemplateId)
                    ->where('date', $todayString)
                    ->lockForUpdate()
                    ->sum('pax_used');
                
                // Get booking total quota
                $booking = $voucher->booking;
                $additionIds = $voucher->addition_facility_ids
                    ? array_map('intval', explode(',', $voucher->addition_facility_ids))
                    : [];
                $totalQuota = (int) ($booking->total_pax + $booking->extra_beds
                    + (in_array($facilityTemplateId, $additionIds) ? ($voucher->addition ?? 0) : 0));
                
                if ($voucher->facility_template_id) {
                    $allowedFacilityIds = array_map('intval', explode(',', $voucher->facility_template_id));
                    if (!in_array($facilityTemplateId, $allowedFacilityIds, true)) {
                        throw new VoucherException('Facility is not linked to this voucher.', 422);
                    }
                }

                // Check if facility is linked to this booking
                $bookingFacility = $booking->bookingFacilities()
                    ->where('facility_template_id', $facilityTemplateId)
                    ->first();
                
                if (!$bookingFacility) {
                    throw new VoucherException('Facility is not linked to this booking.', 422);
                }
                
                // Check if facility is available today
                $start = $bookingFacility->start_date->format('Y-m-d');
                $end = $bookingFacility->end_date->format('Y-m-d');
                $facilityCode = $bookingFacility->facilityTemplate->code;
                $oneTimeFacilityCodes = ['SNACK', 'JOURNAL', 'FEED'];
                $isOneTimeFacility = in_array($facilityCode, $oneTimeFacilityCodes);
                
                if ($isOneTimeFacility) {
                    // One-time facilities: only available on check-in date and can only be redeemed once
                    if ($todayString !== $start) {
                        throw new VoucherException('This facility is only available on check-in date.', 422);
                    }
                    
                    if ($totalUsedToday > 0) {
                        throw VoucherException::expired();
                    }
                } else {
                    // Daily facilities: available within date range, quota resets daily
                    if ($todayString < $start || $todayString > $end) {
                        throw new VoucherException('This facility is not valid today.', 422);
                    }
                }

                $facilityQuota = $bookingFacility->quota_total ?? $totalQuota;
                $quotaRemaining = max(0, $facilityQuota - $totalUsedToday);
                
                if ($quotaRemaining <= 0) {
                    throw VoucherException::expired();
                }

                if ($paxUsed > $quotaRemaining) {
                    throw VoucherException::quotaExceeded();
                }

                $now = now();
                $remainingQuota = $quotaRemaining - $paxUsed;

                $log = RedemptionLog::query()->create([
                    'guest_voucher_id' => $voucher->id,
                    'guest_id' => $voucher->guest_id,
                    'booking_id' => $voucher->booking_id,
                    'facility_template_id' => $facilityTemplateId,
                    'outlet_id' => $outlet->id,
                    'user_id' => $user->id,
                    'pax_used' => $paxUsed,
                    'remaining_quota' => $remainingQuota,
                    'date' => $today->toDateString(),
                    'time' => $now->toTimeString(),
                    'ip_address' => request()->ip(),
                ]);

                $this->logScan($qrCode, $voucher, $outlet, $user, 'success');
                $this->audit->log('voucher.redeemed', $voucher, null, $log->toArray());

                // Increment analytics counter
                $this->cache->incrementRedemptionCount($facilityTemplateId, $todayString);

                // Invalidate cache for this voucher
                $this->cache->invalidateVoucher($voucher);

                // Check if all facilities are fully redeemed
                $this->updateVoucherStatusIfFullyRedeemed($voucher);

                return $log->load(['guestVoucher', 'guest', 'booking', 'facilityTemplate', 'outlet', 'user']);
            });
        } catch (VoucherException $e) {
            // Log the failed scan attempt outside the transaction
            $result = $this->mapExceptionToScanResult($e, $voucher);
            $this->logScan($qrCode, $voucher, $outlet, $user, $result);

            throw $e;
        } catch (\Exception $e) {
            // Log unexpected errors
            $this->logScan($qrCode, $voucher, $outlet, $user, 'system_error');

            throw $e;
        } finally {
            if ($lock) {
                $lock->release();
            }
        }
    }

    public function mapExceptionToScanResult(\Exception $e, ?GuestVoucher $voucher): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Too many scan attempts')) {
            return 'rate_limit_exceeded';
        }

        if (!$voucher) {
            return 'not_found';
        }

        return match (true) {
            str_contains($message, 'not found') => 'not_found',
            str_contains($message, 'no longer active') => 'voucher_not_active',
            str_contains($message, 'not currently checked in') => 'booking_not_checked_in',
            str_contains($message, 'not yet valid') => 'outside_stay_period',
            str_contains($message, 'has expired') => 'outside_stay_period',
            str_contains($message, 'different property') => 'invalid_outlet',
            str_contains($message, 'not linked') => 'facility_not_linked',
            str_contains($message, 'not valid today') => 'invalid_date',
            str_contains($message, 'quota exceeded') => 'quota_exceeded',
            str_contains($message, 'Another redemption is in progress') => 'lock_failed',
            default => 'validation_error',
        };
    }

    private function updateVoucherStatusIfFullyRedeemed(GuestVoucher $voucher): void
    {
        $timezone = $voucher->property?->timezone
            ?? $voucher->booking?->property?->timezone
            ?? 'UTC';
        $today = Carbon::today($timezone);
        $statuses = $voucher->getFacilityStatuses($today);

        // Check if all available facilities for today are fully redeemed
        $allFullyRedeemed = $statuses
            ->filter(fn($status) => $status->is_available)
            ->every(fn($status) => $status->quota_remaining === 0);

        if ($allFullyRedeemed && $statuses->where('is_available', true)->isNotEmpty()) {
            $voucher->update(['status' => VoucherStatus::Redeemed]);
            $this->audit->log('voucher.status_changed', $voucher, ['status' => VoucherStatus::Active->value], ['status' => VoucherStatus::Redeemed->value]);
        }
    }

    public function checkAndExpireIfNeeded(GuestVoucher $voucher): bool
    {
        if ($voucher->status !== VoucherStatus::Active) {
            return false;
        }

        if ($voucher->category === 'temporary') {
            $expiresAt = $voucher->expires_at;
            if ($expiresAt) {
                $timezone = $voucher->property?->timezone ?? 'UTC';
                $currentDateTime = Carbon::now($timezone);

                if ($currentDateTime->gte($expiresAt)) {
                    $voucher->update(['status' => VoucherStatus::Expired]);
                    $this->audit->log(
                        'voucher.auto_expired',
                        $voucher,
                        ['status' => VoucherStatus::Active->value],
                        ['status' => VoucherStatus::Expired->value]
                    );
                    return true;
                }
            }

            return false;
        }

        $timezone = $voucher->booking->property->timezone ?? 'UTC';
        $currentDateTime = Carbon::now($timezone);
        $checkOutDate = Carbon::parse($voucher->booking->check_out)
            ->setTimezone($timezone)
            ->startOfDay()
            ->setTime(21, 0, 0); // 9 PM on checkout date

        if ($currentDateTime->gte($checkOutDate)) {
            $voucher->update(['status' => VoucherStatus::Expired]);
            $this->audit->log(
                'voucher.auto_expired',
                $voucher,
                ['status' => VoucherStatus::Active->value],
                ['status' => VoucherStatus::Expired->value]
            );
            return true;
        }

        return false;
    }

    private function buildQrCode(string $guestName, string $roomCode, string $roomName, string $date): string
    {
        $guestNameClean = preg_replace('/[^a-zA-Z0-9]/', '', $guestName);
        $roomCodeClean = preg_replace('/[^a-zA-Z0-9]/', '', $roomCode);
        $roomNameClean = preg_replace('/[^a-zA-Z0-9]/', '', $roomName);

        // Random entropy to prevent QR code enumeration
        $randomPart = Str::random(16);
        $baseQrCode = "{$guestNameClean}+{$roomCodeClean}+{$randomPart}+{$date}";

        $qrCode = $baseQrCode;
        $counter = 1;

        while (GuestVoucher::query()->where('qr_code', $qrCode)->exists()) {
            $qrCode = "{$baseQrCode}-{$counter}";
            $counter++;
        }

        return $qrCode;
    }

    private function resolveTemporaryExpiry(array $data, Property $property): Carbon
    {
        $timezone = $property->timezone ?? 'UTC';
        $expirationType = (string) ($data['expiration_type'] ?? 'date');
        $value = $data['expiration_value'] ?? null;

        if ($expirationType === 'hour') {
            $hours = (int) $value;
            if ($hours <= 0) {
                throw new VoucherException('Temporary vouchers require a positive hour value.', 422);
            }

            return Carbon::now($timezone)->addHours($hours);
        }

        $date = Carbon::parse($value ?? now($timezone)->toDateString(), $timezone);
        if ($date->isPast()) {
            throw new VoucherException('Temporary voucher expiration date must be in the future.', 422);
        }

        return $date->endOfDay();
    }

    public function logScan(
        string $qrCode,
        ?GuestVoucher $voucher,
        ?Outlet $outlet,
        User $user,
        string $result,
    ): void {
        QrScanLog::query()->create([
            'qr_code' => $qrCode,
            'secure_token' => $voucher?->secure_token,
            'guest_voucher_id' => $voucher?->id,
            'outlet_id' => $outlet?->id,
            'user_id' => $user->id,
            'scan_result' => $result,
            'scanned_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
