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
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
}
