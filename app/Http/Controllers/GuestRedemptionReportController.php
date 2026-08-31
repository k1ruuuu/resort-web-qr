<?php

namespace App\Http\Controllers;

use App\Models\GuestVoucher;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestRedemptionReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $dateString = $request->input('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateString)->startOfDay();
        $propertyId = $request->integer('property_id') ?: null;
        $statusFilter = $request->input('status', 'all');

        $vouchers = GuestVoucher::query()
            ->with([
                'booking.guest',
                'booking.room',
                'booking.property',
                'property',
                'guest',
                'redemptionLogs' => function ($q) use ($date) {
                    $q->where('date', $date->toDateString());
                }
            ])
            ->when($propertyId, function ($q) use ($propertyId) {
                $q->where(function($sub) use ($propertyId) {
                    $sub->where('property_id', $propertyId)
                        ->orWhereHas('booking', fn($b) => $b->where('property_id', $propertyId));
                });
            })
            ->get()
            ->filter(function ($voucher) use ($date) {
                if ($voucher->status !== \App\Enums\VoucherStatus::Active) {
                    return false;
                }

                if ($voucher->category === 'temporary') {
                    if ($voucher->expires_at && Carbon::parse($voucher->expires_at)->lt($date->copy()->startOfDay())) {
                        return false;
                    }
                    return true;
                }

                if ($voucher->booking) {
                    $checkIn = Carbon::parse($voucher->booking->check_in->toDateString())->startOfDay();
                    $checkOut = Carbon::parse($voucher->booking->check_out->toDateString())->startOfDay();
                    return $date->between($checkIn, $checkOut);
                }

                return false;
            });

        $reportData = $vouchers->map(function ($voucher) use ($date) {
            $redeemedPax = $voucher->redemptionLogs->sum('pax_used');
            $hasRedeemed = $redeemedPax > 0;
            
            $guestName = $voucher->booking ? $voucher->booking->guest?->full_name : ($voucher->guest ? $voucher->guest->full_name : $voucher->guest_name);
            $bookingCode = $voucher->booking ? $voucher->booking->booking_code : 'TEMPORARY';
            $room = $voucher->booking ? ($voucher->booking->room ? $voucher->booking->room->number : $voucher->booking->room_label) : '-';
            
            $paxLimit = $voucher->booking ? ($voucher->booking->total_pax + $voucher->booking->extra_beds) : ($voucher->pax_limit ?? 1);
            if ($voucher->addition && $date->toDateString() === $voucher->addition_date?->toDateString()) {
                $paxLimit += $voucher->addition;
            }
            
            $facilityStatuses = $voucher->getFacilityStatuses($date);
            
            return (object)[
                'id' => $voucher->id,
                'guest_name' => $guestName ?: 'Unknown',
                'booking_code' => $bookingCode,
                'room' => $room,
                'property_name' => $voucher->booking ? $voucher->booking->property?->name : $voucher->property?->name,
                'pax_limit' => $paxLimit,
                'redeemed_pax' => $redeemedPax,
                'has_redeemed' => $hasRedeemed,
                'facility_statuses' => $facilityStatuses,
            ];
        })->filter(function ($item) use ($statusFilter) {
            if ($statusFilter === 'redeemed') return $item->has_redeemed;
            if ($statusFilter === 'not_redeemed') return !$item->has_redeemed;
            return true;
        })->sortBy('guest_name')->values();

        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();

        return view('reports.guest-redemption', [
            'date' => $date,
            'propertyId' => $propertyId,
            'statusFilter' => $statusFilter,
            'properties' => $properties,
            'reportData' => $reportData,
        ]);
    }
}
