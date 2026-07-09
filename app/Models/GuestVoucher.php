<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use Carbon\Carbon;
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
        'qr_code',
        'secure_token',
        'status',
        'category',
        'generated_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VoucherStatus::class,
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
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

    public function getFacilityStatuses(?Carbon $date = null): Collection
    {
        // Handle temporary vouchers
        if ($this->category === 'temporary' && $this->facility_template_id) {
            $facilityIds = array_map('intval', explode(',', $this->facility_template_id));
            $timezone = $this->property?->timezone ?? 'UTC';
            $date ??= Carbon::today($timezone);
            $dateString = $date->toDateString();
            
            $facilities = \App\Models\FacilityTemplate::query()
                ->whereIn('id', $facilityIds)
                ->where('is_active', true)
                ->get();
            
            $redemptions = RedemptionLog::query()
                ->where('guest_voucher_id', $this->id)
                ->where('date', $dateString)
                ->selectRaw('facility_template_id, SUM(pax_used) as total_used')
                ->groupBy('facility_template_id')
                ->pluck('total_used', 'facility_template_id');
            
            $paxLimit = $this->pax_limit ?? 1;
            
            return $facilities->map(function ($facility) use ($redemptions, $paxLimit) {
                $used = (int) ($redemptions[$facility->id] ?? 0);
                $remaining = max(0, $paxLimit - $used);
                
                return (object) [
                    'facility_template_id' => $facility->id,
                    'name' => $facility->name,
                    'code' => $facility->code,
                    'is_available' => $remaining > 0,
                    'is_one_time' => false,
                    'quota_total' => $paxLimit,
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
        
        $timezone = $this->booking->property->timezone ?? 'UTC';
        $date ??= Carbon::today($timezone);
        $dateString = $date->toDateString();

        $booking = $this->booking;
        $totalQuota = (int) ($this->pax_limit ?? ($booking->total_pax + $booking->extra_beds));

        // NOTE: facility_template_id is used for redemption validation, NOT for display filtering
        // We always show ALL facilities from the booking on the public page
        // The restriction only applies during actual redemption in VoucherService

        $bookingFacilities = $booking->bookingFacilities;

        $redemptions = RedemptionLog::query()
            ->where('guest_voucher_id', $this->id)
            ->where('date', $dateString)
            ->selectRaw('facility_template_id, SUM(pax_used) as total_used')
            ->groupBy('facility_template_id')
            ->pluck('total_used', 'facility_template_id');

        // Get redemption dates for one-time facilities (to track if already used)
        $oneTimeFacilityCodes = ['SNACK', 'JOURNAL', 'FEED']; // Welcome Snack, Dream Journaling, Animal Feeding
        $oneTimeRedemptions = RedemptionLog::query()
            ->where('guest_voucher_id', $this->id)
            ->whereHas('facilityTemplate', function ($q) use ($oneTimeFacilityCodes) {
                $q->whereIn('code', $oneTimeFacilityCodes);
            })
            ->selectRaw('facility_template_id, MIN(date) as first_redeemed_date')
            ->groupBy('facility_template_id')
            ->pluck('first_redeemed_date', 'facility_template_id');

        return $bookingFacilities->map(function ($bf) use ($dateString, $totalQuota, $redemptions, $oneTimeRedemptions, $timezone) {
            $start = $bf->start_date->format('Y-m-d');
            $end = $bf->end_date->format('Y-m-d');
            $facilityCode = $bf->facilityTemplate->code;
            $facilityQuota = (int) ($bf->quota_total ?? $totalQuota);
            
            // Check if this is a one-time facility
            $isOneTimeFacility = in_array($facilityCode, ['SNACK', 'JOURNAL', 'FEED']);
            
            if ($isOneTimeFacility) {
                // One-time facilities: only available on first day (check-in date) and only if not redeemed
                $isAvailable = ($dateString === $start) && !isset($oneTimeRedemptions[$bf->facility_template_id]);
                $used = (int) ($redemptions[$bf->facility_template_id] ?? 0);
                $remaining = $isAvailable ? max(0, $facilityQuota - $used) : 0;
            } else {
                // Daily facilities: available every day within the date range, quota resets daily
                $isAvailable = ($dateString >= $start && $dateString <= $end);
                $used = (int) ($redemptions[$bf->facility_template_id] ?? 0);
                $remaining = $isAvailable ? max(0, $facilityQuota - $used) : 0;
            }

            return (object) [
                'facility_template_id' => $bf->facility_template_id,
                'name' => $bf->facilityTemplate->name,
                'code' => $facilityCode,
                'is_available' => $isAvailable,
                'is_one_time' => $isOneTimeFacility,
                'quota_total' => $isAvailable ? $facilityQuota : 0,
                'quota_used' => $used,
                'quota_remaining' => $isAvailable ? $remaining : 0,
                'start_date' => $bf->start_date,
                'end_date' => $bf->end_date,
            ];
        });
    }
}
