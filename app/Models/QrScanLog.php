<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScanLog extends Model
{
    protected $fillable = [
        'qr_code',
        'qr_token',
        'daily_voucher_id',
        'outlet_id',
        'user_id',
        'scan_result',
        'scanned_at',
        'ip_address',
        'user_agent',
    ];

    public function dailyVoucher(): BelongsTo
    {
        return $this->belongsTo(DailyVoucher::class);
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
