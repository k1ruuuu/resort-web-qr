<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class GuestVoucher extends Model
{
    protected $fillable = [
        'booking_id',
        'guest_id',
        'property_id',
        'facility_template_id',
        'pax_limit',
        'guest_name',
        'phone',
        'qr_code',
        'secure_token',
        'status',
        'category',
        'generated_at',
        'expires_at',
        'addition',
        'addition_facility_ids',
        'addition_map',
        'addition_date',
    ];


    protected function casts(): array
    {
        return [
            'status' => VoucherStatus::class,
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'addition' => 'integer',
            'addition_map' => 'array',
            'addition_date' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function redemptionLogs(): HasMany
    {
        return $this->hasMany(RedemptionLog::class);
    }

    public function qrScanLogs(): HasMany
    {
        return $this->hasMany(QrScanLog::class);
    }

    protected function expiresAtLocal(): Attribute
    {
        return Attribute::get(fn() => $this->expires_at
            ? $this->expires_at->copy()->setTimezone(
                $this->property?->timezone ?? $this->booking?->property?->timezone ?? 'UTC'
            )
            : null);
    }

    public function additionAppliesOn(string $dateString): bool
    {
        if ($dateString === $this->addition_date?->toDateString()) {
            return true;
        }

        if (!$this->addition_facility_ids) {
            return false;
        }

        $additionIds = array_map('intval', explode(',', $this->addition_facility_ids));
        $oneTimeCodes = ['SNACK', 'JOURNAL', 'FEED'];

        // Addition granted to a one-time facility stays valid for the whole stay
        return $this->booking?->bookingFacilities->contains(function ($bf) use ($additionIds, $oneTimeCodes) {
            return $bf->facilityTemplate
                && in_array($bf->facility_template_id, $additionIds, true)
                && ($bf->facilityTemplate->is_one_time ?? in_array($bf->facilityTemplate->code, $oneTimeCodes, true));
        }) ?? false;
    }

    /**
     * Extend one-time facilities by 1 hour past checkout cutoff.
     */
    public function isOneTimeGracePeriodActive(?Carbon $now = null): bool
    {
        if (!$this->booking) {
            return false;
        }

        $tz = $this->property?->timezone ?? $this->booking->property?->timezone ?? 'Asia/Jakarta';
        $now = ($now ? $now->copy() : Carbon::now($tz))->setTimezone($tz);
        $checkOutDate = $this->booking->check_out ? Carbon::parse($this->booking->check_out, $tz)->toDateString() : null;

        // Grace period applies on the check_out date
        if ($checkOutDate && $now->toDateString() === $checkOutDate) {
            $cutoffTime = Setting::get('maintenance.checkout_cutoff', '12:30');
            $cutoff = Carbon::parse($checkOutDate, $tz)->setTimeFromTimeString($cutoffTime);
            $extendedCutoff = $cutoff->copy()->addHour(); // 1 hour past checkout cutoff

            if ($this->booking->checked_out_at) {
                $checkedOutAtLocal = Carbon::parse($this->booking->checked_out_at, $tz);
                $extendedCutoff = $extendedCutoff->max($checkedOutAtLocal->copy()->addHour());
            }

            return $now->lte($extendedCutoff);
        }

        return false;
    }

    public function getFacilityStatuses(?Carbon $date = null): Collection
    {
        $timezone = $this->property?->timezone ?? $this->booking?->property?->timezone ?? 'UTC';
        $date ??= Carbon::today($timezone);
        $dateString = $date->toDateString();
        $addition = $this->addition ?? 0;
        $additionFacilityIds = $this->addition_facility_ids
            ? array_map('intval', explode(',', $this->addition_facility_ids))
            : [];
        $additionMap = $this->addition_map ?? [];
        $allowedFacilityIds = $this->facility_template_id
            ? array_map('intval', explode(',', $this->facility_template_id))
            : [];

        // Accumulate redemptions up to the requested date
        $redemptions = RedemptionLog::query()
            ->where('guest_voucher_id', $this->id)
            ->where('date', '<=', $dateString)
            ->selectRaw('facility_template_id, SUM(pax_used) as total_used')
            ->groupBy('facility_template_id')
            ->pluck('total_used', 'facility_template_id');

        // Handle temporary vouchers
        if ($this->category === 'temporary' && $allowedFacilityIds) {
            $facilities = \App\Models\FacilityTemplate::query()
                ->whereIn('id', $allowedFacilityIds)
                ->where('is_active', true)
                ->get();

            $basePax = $this->pax_limit ?? 1;
            $nowInPropertyTz = Carbon::now($this->property?->timezone ?? 'UTC');
            $isExpired = $this->expires_at !== null && $nowInPropertyTz->gte($this->expires_at);

            return $facilities->map(function ($facility) use ($redemptions, $basePax, $addition, $additionFacilityIds, $additionMap, $isExpired) {
                $add = $additionMap[$facility->id] ?? (in_array($facility->id, $additionFacilityIds) ? $addition : 0);
                $quota = $basePax + $add;
                $used = (int) ($redemptions[$facility->id] ?? 0);
                $remaining = max(0, $quota - $used);
                $status = $isExpired ? 'unavailable' : ($remaining > 0 ? 'available' : 'used');

                return (object) [
                    'facility_template_id' => $facility->id,
                    'name' => $facility->name,
                    'code' => $facility->code,
                    'is_available' => !$isExpired && $remaining > 0,
                    'status' => $status,
                    'is_one_time' => false,
                    'quota_total' => $quota,
                    'quota_used' => $used,
                    'quota_remaining' => $remaining,
                    'start_date' => Carbon::today($this->property?->timezone ?? 'UTC'),
                    'end_date' => $this->expires_at ? Carbon::parse($this->expires_at) : Carbon::today($this->property?->timezone ?? 'UTC'),
                ];
            });
        }

        // Handle standard booking vouchers
        if (!$this->booking) {
            return collect();
        }

        $this->loadMissing(['booking.bookingFacilities.facilityTemplate', 'booking.property']);

        $booking = $this->booking;
        $baseQuota = (int) ($booking->total_pax + $booking->extra_beds);

        $bookingFacilities = $booking->bookingFacilities;

        // Filter by voucher's granted facility IDs so removed facilities don't show
        if ($allowedFacilityIds) {
            $bookingFacilities = $bookingFacilities->filter(fn($bf) =>
                in_array($bf->facility_template_id, $allowedFacilityIds)
            );

            // Include any allowed facility templates that were not yet saved in booking_facilities
            $existingTemplateIds = $bookingFacilities->pluck('facility_template_id')->all();
            $missingTemplateIds = array_diff($allowedFacilityIds, $existingTemplateIds);

            if (!empty($missingTemplateIds)) {
                $missingTemplates = FacilityTemplate::query()
                    ->whereIn('id', $missingTemplateIds)
                    ->where('is_active', true)
                    ->get();

                foreach ($missingTemplates as $template) {
                    $virtualBf = new BookingFacility([
                        'booking_id' => $booking->id,
                        'facility_template_id' => $template->id,
                        'start_date' => $booking->check_in,
                        'end_date' => $booking->check_out,
                        'quota_total' => $baseQuota,
                    ]);
                    $virtualBf->setRelation('facilityTemplate', $template);
                    $bookingFacilities->push($virtualBf);
                }
            }
        }

        $oneTimeFacilityCodes = ['SNACK', 'JOURNAL', 'FEED'];

        // Total usage per facility across the whole stay (used for one-time facilities)
        $everUsedByFacility = RedemptionLog::query()
            ->where('guest_voucher_id', $this->id)
            ->selectRaw('facility_template_id, SUM(pax_used) as total_used')
            ->groupBy('facility_template_id')
            ->pluck('total_used', 'facility_template_id');

        $isOneTimeGrace = $this->isOneTimeGracePeriodActive($date);
        $booking = $this->booking;

        return $bookingFacilities->map(function ($bf) use ($dateString, $baseQuota, $addition, $additionFacilityIds, $additionMap, $redemptions, $everUsedByFacility, $oneTimeFacilityCodes, $timezone, $isOneTimeGrace, $booking) {
            if (!$bf->facilityTemplate) {
                return null;
            }

            // M-12: compare facility dates in the property timezone safely by string
            $start = $bf->start_date ? Carbon::parse($bf->start_date->toDateString(), $timezone)->toDateString() : $dateString;
            $end = $bf->end_date ? Carbon::parse($bf->end_date->toDateString(), $timezone)->toDateString() : $dateString;
            $facilityCode = $bf->facilityTemplate->code;
            $facilityAdd = $additionMap[$bf->facility_template_id] ?? (in_array($bf->facility_template_id, $additionFacilityIds) ? $addition : 0);

            $isOneTimeFacility = $bf->facilityTemplate->is_one_time
                ?? in_array($facilityCode, $oneTimeFacilityCodes);

            // Base quota per day
            $baseDailyQuota = (int) ($bf->quota_total ?? $baseQuota);
            
            // Calculate accumulated quota for daily facilities based on days elapsed (rollover capped at booked nights)
            if ($isOneTimeFacility) {
                $accumulatedQuota = $baseDailyQuota;
            } else {
                // Days elapsed from start date up to the requested date (min 1)
                $daysElapsed = max(0, Carbon::parse($start)->diffInDays(Carbon::parse($dateString))) + 1;
                $maxDays = max(1, (int) ($booking?->nights ?? 1));
                $daysCount = min($daysElapsed, $maxDays);
                $accumulatedQuota = $baseDailyQuota * $daysCount;
            }
            
            // Addition applies once if one-time, or if it was granted on/before the requested date
            $additionApplies = $isOneTimeFacility || ($this->addition_date && $this->addition_date->toDateString() <= $dateString);
            $facilityQuota = $accumulatedQuota + ($additionApplies ? $facilityAdd : 0);

            // Both one-time and daily facilities are available within their date range.
            // One-time facilities also remain available during the 1-hour grace period past checkout cutoff.
            $inPeriod = ($dateString >= $start && $dateString <= $end) || ($isOneTimeGrace && $isOneTimeFacility);
            $isAvailable = $inPeriod;

            $used = (int) (($isOneTimeFacility ? $everUsedByFacility : $redemptions)[$bf->facility_template_id] ?? 0);
            $remaining = max(0, $facilityQuota - $used);
            $status = !$inPeriod ? 'unavailable' : ($isAvailable && $remaining > 0 ? 'available' : 'used');

            return (object) [
                'facility_template_id' => $bf->facility_template_id,
                'name' => $bf->facilityTemplate->name,
                'code' => $facilityCode,
                'is_available' => $isAvailable,
                'status' => $status,
                'is_one_time' => $isOneTimeFacility,
                'quota_total' => $inPeriod ? $facilityQuota : 0,
                'quota_used' => $used,
                'quota_remaining' => $inPeriod ? $remaining : 0,
                'start_date' => $bf->start_date,
                'end_date' => $bf->end_date,
            ];
        })->filter()->values();
    }
}
