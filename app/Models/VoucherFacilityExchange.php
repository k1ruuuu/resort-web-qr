<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherFacilityExchange extends Model
{
    public const ALLOWED_EXCHANGE_CODES = ['DINNER-BBQ', 'DINNER100K'];

    protected $fillable = [
        'guest_voucher_id',
        'exchange_date',
        'from_facility_template_id',
        'to_facility_template_id',
        'pax',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'exchange_date' => 'date',
            'pax' => 'integer',
        ];
    }

    public function guestVoucher(): BelongsTo
    {
        return $this->belongsTo(GuestVoucher::class);
    }

    public function fromFacilityTemplate(): BelongsTo
    {
        return $this->belongsTo(FacilityTemplate::class, 'from_facility_template_id');
    }

    public function toFacilityTemplate(): BelongsTo
    {
        return $this->belongsTo(FacilityTemplate::class, 'to_facility_template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
