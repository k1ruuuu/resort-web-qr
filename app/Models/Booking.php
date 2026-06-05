<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'property_id',
        'guest_id',
        'room_id',
        'booking_code',
        'reference',
        'source',
        'room_label',
        'check_in',
        'check_out',
        'expected_arrival',
        'expected_departure',
        'nights',
        'adults',
        'children',
        'extra_beds',
        'total_pax',
        'status',
        'pms_voucher_ref',        'checked_in_at',
        'checked_out_at',        'checked_in_at',
        'checked_out_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'expected_arrival' => 'date',
            'expected_departure' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'status' => BookingStatus::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bookingFacilities(): HasMany
    {
        return $this->hasMany(BookingFacility::class);
    }

    public function guestVoucher(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(GuestVoucher::class);
    }
}
