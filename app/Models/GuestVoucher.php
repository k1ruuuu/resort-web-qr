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

        $redemptions = RedemptionLog::query()
            ->where('guest_voucher_id', $this->id)
            ->where('date', $dateString)
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

        // Source of truth = voucher's granted facility IDs. The booking snapshot only
        // contributes quota/dates for facilities that were granted at booking time;
        // facilities granted later (e.g. Dinner 100K) have no snapshot row and are
        // synthesized from the template so they appear on the guest page too.
        // Revoking every facility (empty list) shows nothing.
        $bookingFacilitiesById = $booking->bookingFacilities->keyBy('facility_template_id');
        $grantedTemplates = \App\Models\FacilityTemplate::query()
            ->whereIn('id', $allowedFacilityIds)
            ->get()
            ->keyBy('id');

        $bookingFacilities = collect();
        foreach ($allowedFacilityIds as $facilityId) {
            $bf = $bookingFacilitiesById->get($facilityId);
            if (!$bf) {
                $template = $grantedTemplates->get($facilityId);
                if (!$template) {
                    continue;
                }
                $bf = new \App\Models\BookingFacility([
                    'booking_id' => $booking->id,
                    'facility_template_id' => $facilityId,
                    'start_date' => $booking->check_in,
                    'end_date' => $booking->check_out,
                    'quota_total' => null,
                ]);
                $bf->setRelation('facilityTemplate', $template);
            }
            $bookingFacilities->push($bf);
        }

        // Get redemption dates for one-time facilities (to track if already used)
        $oneTimeFacilityCodes = ['SNACK', 'JOURNAL', 'FEED'];
        $oneTimeRedemptions = RedemptionLog::query()
            ->where('guest_voucher_id', $this->id)
            ->whereHas('facilityTemplate', function ($q) use ($oneTimeFacilityCodes) {
                $q->whereIn('code', $oneTimeFacilityCodes);
            })
            ->selectRaw('facility_template_id, MIN(date) as first_redeemed_date')
            ->groupBy('facility_template_id')
            ->pluck('first_redeemed_date', 'facility_template_id');

        // Total usage per facility across the whole stay (used for one-time facilities)
        $everUsedByFacility = RedemptionLog::query()
            ->where('guest_voucher_id', $this->id)
            ->selectRaw('facility_template_id, SUM(pax_used) as total_used')
            ->groupBy('facility_template_id')
            ->pluck('total_used', 'facility_template_id');

        return $bookingFacilities->map(function ($bf) use ($dateString, $baseQuota, $addition, $additionFacilityIds, $additionMap, $redemptions, $oneTimeRedemptions, $everUsedByFacility, $oneTimeFacilityCodes, $timezone) {
            if (!$bf->facilityTemplate) {
                return null;
            }

            // M-12: compare facility dates in the property timezone
            $start = $bf->start_date?->setTimezone($timezone)->toDateString() ?? $dateString;
            $end = $bf->end_date?->setTimezone($timezone)->toDateString() ?? $dateString;
            $facilityCode = $bf->facilityTemplate->code;
            $facilityAdd = $additionMap[$bf->facility_template_id] ?? (in_array($bf->facility_template_id, $additionFacilityIds) ? $addition : 0);

            $isOneTimeFacility = $bf->facilityTemplate->is_one_time
                ?? in_array($facilityCode, $oneTimeFacilityCodes);

            // Addition boosts quota once per stay for one-time facilities; for daily
            // facilities it only applies on the day it was granted (extra beds are
            // already part of the base pax and apply every day)
            $additionApplies = $isOneTimeFacility || $dateString === $this->addition_date?->toDateString();
            $facilityQuota = (int) (($bf->quota_total ?? $baseQuota) + ($additionApplies ? $facilityAdd : 0));

            if ($isOneTimeFacility) {
                // One-time facilities: usable any day within the period, once per stay
                $neverRedeemed = !isset($oneTimeRedemptions[$bf->facility_template_id]);
                $inPeriod = $dateString >= $start && $dateString <= $end;
                $isAvailable = $inPeriod && $neverRedeemed;
            } else {
                // Daily facilities: available within date range, quota resets daily
                $inPeriod = $dateString >= $start && $dateString <= $end;
                $isAvailable = $inPeriod;
            }

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
        });
    }
}
