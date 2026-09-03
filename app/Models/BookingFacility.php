<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingFacility extends Model
{
    protected $fillable = [
        'booking_id',
        'facility_template_id',
        'start_date',
        'end_date',
        'quota_total',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class)->withTrashed();
    }

    public function facilityTemplate(): BelongsTo
    {
        return $this->belongsTo(FacilityTemplate::class);
    }


}
