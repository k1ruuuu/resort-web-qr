<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScanLog extends Model
{
    protected $appends = ['scanned_at_local'];

    protected $fillable = [
        'qr_code',
        'secure_token',
        'guest_voucher_id',
        'facility_template_id',
        'outlet_id',
        'user_id',
        'scan_result',
        'scanned_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    public function guestVoucher(): BelongsTo
    {
        return $this->belongsTo(GuestVoucher::class);
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

    /**
     * Timestamp in the property's local timezone (scans are stored in UTC).
     * Falls back to Asia/Jakarta (WIB) when no property context is available.
     */
    protected function scannedAtLocal(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->scanned_at
                ? $this->scanned_at->copy()->setTimezone(
                    $this->outlet?->property?->timezone
                        ?? $this->guestVoucher?->property?->timezone
                        ?? 'Asia/Jakarta'
                )
                : null,
        );
    }
}
