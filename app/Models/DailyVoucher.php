<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyVoucher extends Model
{
    protected $fillable = [
        'qr_code',
        'qr_token',
        'public_token',
        'booking_id',
        'booking_facility_id',
        'facility_template_id',
        'valid_date',
        'quota_total',
        'quota_remaining',
        'status',
        'generated_at',
        'redeemed_at',
        'redeemed_by_user_id',
        'redeemed_at_outlet_id',
    ];

    protected function casts(): array
    {
        return [
            'valid_date' => 'date',
            'status' => VoucherStatus::class,
            'generated_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingFacility(): BelongsTo
    {
        return $this->belongsTo(BookingFacility::class);
    }

    public function facilityTemplate(): BelongsTo
    {
        return $this->belongsTo(FacilityTemplate::class);
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
    }

    public function redeemedAtOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'redeemed_at_outlet_id');
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(VoucherUsageLog::class);
    }

    public function qrScanLogs(): HasMany
    {
        return $this->hasMany(QrScanLog::class);
    }
}
