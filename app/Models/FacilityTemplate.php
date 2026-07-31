<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityTemplate extends Model
{
    protected $fillable = [
        'property_id',
        'name',
        'code',
        'description',
        'is_active',
        'is_one_time',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_one_time' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function bookingFacilities(): HasMany
    {
        return $this->hasMany(BookingFacility::class);
    }

    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class)
            ->withTimestamps();
    }

    /**
     * Item 4: facility-in-use guard. booking_facilities, redemption_logs and
     * outlets.facility_template_id cascade on facility deletion — deleting or
     * reassigning a referenced facility would destroy history, so it is blocked.
     */
    protected static function booted(): void
    {
        static::deleting(function (FacilityTemplate $facility) {
            if (self::isReferenced($facility)) {
                throw new \RuntimeException(
                    'Facility is referenced by bookings, vouchers, outlets or redemption history and cannot be deleted.'
                );
            }
        });

        static::updating(function (FacilityTemplate $facility) {
            if ($facility->isDirty('property_id') && self::isReferenced($facility)) {
                throw new \RuntimeException(
                    'Facility is referenced by bookings, vouchers, outlets or redemption history and cannot be reassigned to another property.'
                );
            }
        });
    }

    private static function isReferenced(FacilityTemplate $facility): bool
    {
        $id = $facility->id;

        return $facility->bookingFacilities()->exists()
            || RedemptionLog::query()->where('facility_template_id', $id)->exists()
            || $facility->outlets()->exists()
            || Outlet::query()->where('facility_template_id', $id)->exists()
            // guest_vouchers.facility_template_id / addition_facility_ids are comma-separated
            || GuestVoucher::query()
                ->whereRaw('FIND_IN_SET(?, facility_template_id)', [$id])
                ->exists()
            || GuestVoucher::query()
                ->whereRaw('FIND_IN_SET(?, addition_facility_ids)', [$id])
                ->exists()
            // guest_vouchers.addition_map is a JSON object keyed by facility id
            || GuestVoucher::query()
                ->whereRaw('JSON_EXTRACT(addition_map, ?) IS NOT NULL', ['$."' . $id . '"'])
                ->exists();
    }
}
