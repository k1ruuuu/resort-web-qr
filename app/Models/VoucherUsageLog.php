<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherUsageLog extends Model
{
    protected $fillable = [
        'daily_voucher_id',
        'outlet_id',
        'user_id',
        'action',
        'pax_used',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

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
