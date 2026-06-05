<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedemptionLog extends Model
{
    protected $fillable = [
        'guest_voucher_id',
        'guest_id',
        'booking_id',
        'facility_template_id',
        'outlet_id',
        'user_id',
        'pax_used',
        'remaining_quota',
        'date',
        'time',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function guestVoucher(): BelongsTo
    {
        return $this->belongsTo(GuestVoucher::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function facilityTemplate(): BelongsTo
    {
        return $this->belongsTo(FacilityTemplate::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
