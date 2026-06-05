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
        'qr_code',
        'secure_token',
        'status',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VoucherStatus::class,
            'generated_at' => 'datetime',
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
        $this->loadMissing(['booking.bookingFacilities.facilityTemplate', 'booking.property']);
        
        $timezone = $this->booking->property->timezone ?? 'UTC';
        $date ??= Carbon::today($timezone);
        $dateString = $date->toDateString();

        $booking = $this->booking;
        $totalQuota = (int) ($booking->total_pax + $booking->extra_beds);

        $redemptions = RedemptionLog::query()
            ->where('guest_voucher_id', $this->id)
            ->where('date', $dateString)
            ->selectRaw('facility_template_id, SUM(pax_used) as total_used')
            ->groupBy('facility_template_id')
            ->pluck('total_used', 'facility_template_id');

        return $booking->bookingFacilities->map(function ($bf) use ($dateString, $totalQuota, $redemptions, $timezone) {
            $start = $bf->start_date->format('Y-m-d');
            $end = $bf->end_date->format('Y-m-d');
            $isAvailable = ($dateString >= $start && $dateString <= $end);

            $used = (int) ($redemptions[$bf->facility_template_id] ?? 0);
            $remaining = $isAvailable ? max(0, $totalQuota - $used) : 0;

            return (object) [
                'facility_template_id' => $bf->facility_template_id,
                'name' => $bf->facilityTemplate->name,
                'code' => $bf->facilityTemplate->code,
                'is_available' => $isAvailable,
                'quota_total' => $isAvailable ? $totalQuota : 0,
                'quota_used' => $used,
                'quota_remaining' => $remaining,
                'start_date' => $bf->start_date,
                'end_date' => $bf->end_date,
            ];
        });
    }
}
