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

        $request->validate([
            'date' => 'nullable|date',
            'status' => 'nullable|in:all,redeemed,not_redeemed',
        ]);

        $dateString = $request->input('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateString)->startOfDay();
        $propertyId = $request->integer('property_id') ?: null;
        $statusFilter = $request->input('status', 'all');

        $user = auth()->user();
        if (!$user->hasRole('super-admin')) {
            $allowed = $user->properties()->pluck('property_id');
            abort_unless($propertyId === null || $allowed->contains($propertyId), 403);
        }

        $dateStr = $date->toDateString();

        $vouchers = GuestVoucher::query()
            ->where('status', \App\Enums\VoucherStatus::Active)
            ->where(function ($q) use ($dateStr) {
                // Temporary vouchers not expired before selected date
                $q->where(function ($temp) use ($dateStr) {
                    $temp->where('category', 'temporary')
                        ->where(function ($exp) use ($dateStr) {
                            $exp->whereNull('expires_at')
                                ->orWhereDate('expires_at', '>=', $dateStr);
                        });
                })
                // Standard booking vouchers where date is between check_in and check_out
                ->orWhereHas('booking', function ($b) use ($dateStr) {
                    $b->whereDate('check_in', '<=', $dateStr)
                      ->whereDate('check_out', '>=', $dateStr);
                });
            })
            ->with([
                'booking.guest',
                'booking.room',
                'booking.property',
                'property',
                'guest',
                'redemptionLogs' => function ($q) use ($dateStr) {
                    $q->where('date', $dateStr);
                }
            ])
            ->when($propertyId, function ($q) use ($propertyId) {
                $q->where(function($sub) use ($propertyId) {
                    $sub->where('property_id', $propertyId)
                        ->orWhereHas('booking', fn($b) => $b->where('property_id', $propertyId));
                });
            })
            ->get();

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
