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
use App\Services\FacilityScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    private readonly FacilityScheduleService $schedules;

    public function __construct(
        private readonly AuditService $audit,
        private readonly BookingService $bookings,
        private readonly StayQuotaService $quota,
        private readonly RedisLockService $locks,
        private readonly RedisCacheService $cache,
        ?FacilityScheduleService $schedules = null,
    ) {
        $this->schedules = $schedules ?? app(FacilityScheduleService::class);
    }

    public function generateForBooking(Booking $booking): GuestVoucher
    {
        if ($booking->status !== BookingStatus::CheckIn) {
            throw VoucherException::bookingNotCheckedIn();
        }

        return $this->createVoucherForBooking($booking);
    }

    public function generateTemporaryVoucher(array $data): GuestVoucher
    {
        $property = Property::query()->findOrFail($data['property_id']);
        $guestName = trim((string) ($data['guest_name'] ?? 'Temporary Guest'));
        $phone = $data['phone'] ?? null;
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

            return DB::transaction(function () use ($guestName, $phone, $category, $expiresAt, $property, $qrCode, $secureToken, $facilityTemplateIds, $paxLimit) {
                $voucher = GuestVoucher::query()->create([
                    'booking_id' => null,
                    'guest_id' => null,
                    'property_id' => $property->id,
                    'facility_template_id' => $facilityTemplateIds ? implode(',', $facilityTemplateIds) : null,
                    'pax_limit' => $paxLimit,
                    'guest_name' => $guestName,
                    'phone' => $phone,
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
        // Allow revoking ALL facilities: keep the empty array instead of
        // throwing, so Not Granted works on every row.
        if (!empty($facilityTemplateIds)) {
            $validFacilityCount = FacilityTemplate::query()
                ->where('property_id', $voucher->property_id)
                ->where('is_active', true)
                ->whereIn('id', $facilityTemplateIds)
                ->count();

            if ($validFacilityCount !== count($facilityTemplateIds)) {
                throw new VoucherException('One or more selected facilities are invalid for this voucher.', 422);
            }
        }

        $additionMap = null;
        $additionTotal = 0;
        $additionFacilityIds = [];
        if (isset($data['addition_map']) && is_array($data['addition_map'])) {
            $additionMap = [];
            foreach ($data['addition_map'] as $facilityId => $amount) {
                $amount = max(0, (int) $amount);
                if ($amount > 0) {
                    $additionMap[(int) $facilityId] = $amount;
                    $additionTotal += $amount;
                    $additionFacilityIds[] = (int) $facilityId;
                }
            }
            if (empty($additionMap)) {
                $additionMap = null;
            }
        }

        $lock = $this->locks->lockVoucherGeneration($voucher->id, 15);
        if (!$lock) {
            throw new VoucherException('Another voucher update is in progress. Please wait.', 409);
        }

        try {
            return DB::transaction(function () use ($voucher, $facilityTemplateIds, $additionMap, $additionTotal, $additionFacilityIds) {
                $voucher->forceFill([
                    'facility_template_id' => implode(',', $facilityTemplateIds),
                    'addition_map' => $additionMap,
                    'addition' => $additionTotal,
                    'addition_facility_ids' => $additionFacilityIds ? implode(',', $additionFacilityIds) : null,
                    'addition_date' => $additionMap
                        ? Carbon::today($voucher->property?->timezone ?? 'UTC')->toDateString()
                        : null,
                ])->save();

                // L-07: keep the cached voucher data in sync
                $this->cache->invalidateVoucher($voucher);

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

            $guestName = $booking->guest?->full_name ?? 'Guest #' . $booking->guest_id;
            $roomCode = $booking->room?->code ?? $booking->room?->number ?? 'ROOM';
            $roomName = $booking->room?->label ?? $booking->room?->roomType?->name ?? 'Room';
            $date = $booking->check_in->format('Y-m-d');

            $qrCode = $this->buildQrCode($guestName, $roomCode, $roomName, $date);

            $secureToken = (string) Str::random(32);

            $facilityTemplateIds = $booking->bookingFacilities
                ->pluck('facility_template_id')
                ->filter()
                ->values()
                ->toArray();

            $voucher = DB::transaction(function () use ($booking, $qrCode, $secureToken, $guestName, $facilityTemplateIds) {
                $voucher = GuestVoucher::query()->where('booking_id', $booking->id)->first();

                if (!$voucher) {
                    $voucher = GuestVoucher::query()->create([
                        'booking_id' => $booking->id,
                        'guest_id' => $booking->guest_id,
                        'property_id' => $booking->property_id,
                        'facility_template_id' => $facilityTemplateIds ? implode(',', $facilityTemplateIds) : null,
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

                if ($voucher->booking && $voucher->booking->status !== BookingStatus::CheckIn) {
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

                    // CRITICAL: Only allow redemption of facilities granted to this voucher
                    if ($voucher->facility_template_id) {
                        $allowedFacilityIds = array_map('intval', explode(',', $voucher->facility_template_id));
                        if (!in_array($facilityTemplateId, $allowedFacilityIds, true)) {
                            throw new VoucherException('Facility is not linked to this voucher.', 422);
                        }
                    }

                    $facility = FacilityTemplate::query()
                        ->where('id', $facilityTemplateId)
                        ->where('property_id', $voucher->property_id)
                        ->where('is_active', true)
                        ->first();

                    if (!$facility) {
                        throw new VoucherException('Facility is not linked to this voucher.', 422);
                    }

                    // Time window checking has been disabled per user request;
                    
                    $today = Carbon::today($timezone);
                    $todayString = $today->toDateString();
                    
                    // CRITICAL: Calculate quota from database WITH row-level locking to prevent race conditions
                    $totalUsedUpToToday = DB::table('redemption_logs')
                        ->where('guest_voucher_id', $voucher->id)
                        ->where('facility_template_id', $facilityTemplateId)
                        ->where('date', '<=', $todayString)
                        ->lockForUpdate()
                        ->sum('pax_used');
                    
                    $additionMap = $voucher->addition_map ?? [];
                    $additionIds = $voucher->addition_facility_ids
                        ? array_map('intval', explode(',', $voucher->addition_facility_ids))
                        : [];
                    $basePax = $voucher->pax_limit ?? 1;
                    $add = $additionMap[$facilityTemplateId] ?? (in_array($facilityTemplateId, $additionIds) ? ($voucher->addition ?? 0) : 0);
                    // Addition only applies on the day it was granted (one-time boost)
                    if ($todayString !== ($voucher->addition_date?->toDateString())) {
                        $add = 0;
                    }
                    $paxLimit = $basePax + $add;
                    $quotaRemaining = max(0, $paxLimit - $totalUsedUpToToday);
                    
                    if ($quotaRemaining <= 0) {
                        throw VoucherException::quotaExhausted();
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

                    $this->logScan($qrCode, $voucher, $outlet, $user, 'success', $facilityTemplateId);
                    $this->audit->log('voucher.redeemed', $voucher, null, $log->toArray());
                    $this->cache->invalidateVoucher($voucher);

                    return $log->load(['guestVoucher', 'guest', 'booking', 'facilityTemplate', 'outlet', 'user']);
                }

                if (!$voucher->booking) {
                    throw new VoucherException('Voucher has no associated booking.', 422);
                }

                $timezone = $voucher->booking->property->timezone ?? 'UTC';
                $currentDateTime = Carbon::now($timezone);
                $checkInDate = Carbon::parse($voucher->booking->check_in->toDateString(), $timezone)->startOfDay();
                $checkOutDate = Carbon::parse($voucher->booking->check_out->toDateString(), $timezone)->startOfDay();
                
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
                $totalUsedUpToToday = DB::table('redemption_logs')
                    ->where('guest_voucher_id', $voucher->id)
                    ->where('facility_template_id', $facilityTemplateId)
                    ->where('date', '<=', $todayString)
                    ->lockForUpdate()
                    ->sum('pax_used');
                
                // Get booking total quota
                $booking = $voucher->booking;
                $additionMap = $voucher->addition_map ?? [];
                $additionIds = $voucher->addition_facility_ids
                    ? array_map('intval', explode(',', $voucher->addition_facility_ids))
                    : [];
                
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
                
                // Check if facility is available today (M-12: compare in the property timezone)
                $tz = $voucher->booking->property->timezone ?? 'UTC';
                $start = $bookingFacility->start_date->setTimezone($tz)->toDateString();
                $end = $bookingFacility->end_date->setTimezone($tz)->toDateString();
                $facilityCode = $bookingFacility->facilityTemplate->code;

                // Time window checking has been disabled per user request;
                
                $oneTimeFacilityCodes = ['SNACK', 'JOURNAL', 'FEED'];
                // Item 3: DB flag overrides the code heuristic; null keeps legacy behavior
                $isOneTimeFacility = $bookingFacility->facilityTemplate->is_one_time
                    ?? in_array($facilityCode, $oneTimeFacilityCodes);
                
                // Both one-time and daily facilities must be within their valid date range
                if ($todayString < $start || $todayString > $end) {
                    throw new VoucherException('This facility is not valid today.', 422);
                }

                // Checkout limit has been disabled

                $everUsed = 0;
                if ($isOneTimeFacility) {
                    $everUsed = DB::table('redemption_logs')
                        ->where('guest_voucher_id', $voucher->id)
                        ->where('facility_template_id', $facilityTemplateId)
                        ->lockForUpdate()
                        ->sum('pax_used');
                }

                $add = $additionMap[$facilityTemplateId] ?? (in_array($facilityTemplateId, $additionIds) ? ($voucher->addition ?? 0) : 0);
                
                $baseDailyQuota = $bookingFacility->quota_total ?? (int) ($voucher->booking->total_pax + $voucher->booking->extra_beds);
                
                if ($isOneTimeFacility) {
                    $accumulatedQuota = $baseDailyQuota;
                } else {
                    $daysElapsed = max(0, Carbon::parse($start)->diffInDays(Carbon::parse($todayString))) + 1;
                    $accumulatedQuota = $baseDailyQuota * $daysElapsed;
                }
                
                // Addition applies once if one-time, or if it was granted on/before today
                $additionApplies = $isOneTimeFacility || ($voucher->addition_date && $voucher->addition_date->toDateString() <= $todayString);
                
                $facilityQuota = $accumulatedQuota + ($additionApplies ? $add : 0);
                
                $usageSum = $isOneTimeFacility ? $everUsed : $totalUsedUpToToday;
                $quotaRemaining = max(0, $facilityQuota - $usageSum);
                
                if ($quotaRemaining <= 0) {
                    throw VoucherException::quotaExhausted();
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

                $this->logScan($qrCode, $voucher, $outlet, $user, 'success', $facilityTemplateId);
                $this->audit->log('voucher.redeemed', $voucher, null, $log->toArray());

                // Increment analytics counter
                $this->cache->incrementRedemptionCount($facilityTemplateId, $todayString);

                // Invalidate cache for this voucher
                $this->cache->invalidateVoucher($voucher);

                return $log->load(['guestVoucher', 'guest', 'booking', 'facilityTemplate', 'outlet', 'user']);
            });
        } catch (VoucherException $e) {
            // Log the failed scan attempt outside the transaction
            $result = $this->mapExceptionToScanResult($e, $voucher);
            $this->logScan($qrCode, $voucher, $outlet, $user, $result, $facilityTemplateId);

            throw $e;
        } catch (\Exception $e) {
            // Log unexpected errors
            $this->logScan($qrCode, $voucher, $outlet, $user, 'system_error', $facilityTemplateId);

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
            str_contains($message, 'diluar jam yang telah ditentukan') => 'outside_redemption_hours',
            str_contains($message, 'not found') => 'not_found',
            str_contains($message, 'no longer active') => 'voucher_not_active',
            str_contains($message, 'not currently checked in') => 'booking_not_checked_in',
            str_contains($message, 'not yet valid') => 'outside_stay_period',
            str_contains($message, 'has expired') => 'outside_stay_period',
            str_contains($message, 'different property') => 'invalid_outlet',
            str_contains($message, 'not linked') => 'facility_not_linked',
            str_contains($message, 'not valid today') => 'invalid_date',
            str_contains($message, 'quota exceeded') => 'quota_exceeded',
            str_contains($message, 'fully used') => 'quota_exceeded',
            str_contains($message, 'Another redemption is in progress') => 'lock_failed',
            default => 'validation_error',
        };
    }

    public function checkAndExpireIfNeeded(GuestVoucher $voucher): bool
    {
        // Repair legacy vouchers that were auto-marked 'redeemed' when all of
        // today's facilities were used up. Quota resets daily, so the voucher
        // stays usable until the real expiry (checkout / temporary expiry).
        if ($voucher->status === VoucherStatus::Redeemed) {
            $voucher->update(['status' => VoucherStatus::Active]);
            $this->audit->log(
                'voucher.status_changed',
                $voucher,
                ['status' => VoucherStatus::Redeemed->value],
                ['status' => VoucherStatus::Active->value]
            );
            $voucher->refresh();
        }

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
                    $voucher->refresh();
                    return true;
                }
            }

            return false;
        }

        if (!$voucher->booking) {
            return false;
        }

        $timezone = $voucher->booking->property->timezone ?? 'UTC';
        $currentDateTime = Carbon::now($timezone);
        $checkOutDate = Carbon::parse($voucher->booking->check_out->toDateString(), $timezone)
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
            $voucher->refresh();
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
        $appTimezone = config('app.timezone', 'UTC');
        $expirationType = (string) ($data['expiration_type'] ?? 'date');
        $value = $data['expiration_value'] ?? null;

        if ($expirationType === 'hour') {
            $hours = (int) $value;
            if ($hours <= 0) {
                throw new VoucherException('Temporary vouchers require a positive hour value.', 422);
            }

            return Carbon::now($timezone)->addHours($hours)->setTimezone($appTimezone);
        }

        $date = Carbon::parse($value ?? now($timezone)->toDateString(), $timezone);
        if ($date->isPast()) {
            throw new VoucherException('Temporary voucher expiration date must be in the future.', 422);
        }

        return $date->endOfDay()->setTimezone($appTimezone);
    }

    public function logScan(
        string $qrCode,
        ?GuestVoucher $voucher,
        ?Outlet $outlet,
        User $user,
        string $result,
        ?int $facilityTemplateId = null,
    ): void {
        QrScanLog::query()->create([
            'qr_code' => $qrCode,
            'secure_token' => $voucher?->secure_token,
            'guest_voucher_id' => $voucher?->id,
            'facility_template_id' => $facilityTemplateId,
            'outlet_id' => $outlet?->id,
            'user_id' => $user->id,
            'scan_result' => $result,
            'scanned_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
