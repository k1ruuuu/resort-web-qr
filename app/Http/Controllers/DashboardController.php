<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\RedemptionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        // Dashboard is the landing page after login for every authenticated role
        // (e.g. redeem-only staff without bookings.view). Data queries below are
        // property-scoped for non-super-admins; the sidebar hides what a role can't do.
        $user = auth()->user();
        $propertyIds = $user->hasRole('super-admin') ? null : $user->properties()->pluck('property_id');

        $totalGuests = Guest::query()->count();

        $activeGuests = Booking::query()
            ->where('status', BookingStatus::CheckIn)
            ->when($propertyIds, fn($q) => $q->whereIn('property_id', $propertyIds))
            ->sum('total_pax');

        $bookingCount = Booking::query()
            ->when($propertyIds, fn($q) => $q->whereIn('property_id', $propertyIds))
            ->count();

        $todayStr = Carbon::today()->toDateString();
        
        $activeBookings = Booking::query()
            ->where('status', BookingStatus::CheckIn)
            ->when($propertyIds, fn($q) => $q->whereIn('property_id', $propertyIds))
            ->with('bookingFacilities')
            ->get();

        $totalQuotaToday = 0;
        foreach ($activeBookings as $booking) {
            $dailyQuota = (int) ($booking->total_pax + $booking->extra_beds);
            foreach ($booking->bookingFacilities as $bf) {
                $start = $bf->start_date->format('Y-m-d');
                $end = $bf->end_date->format('Y-m-d');
                if ($todayStr >= $start && $todayStr <= $end) {
                    $totalQuotaToday += $dailyQuota;
                }
            }
        }

        $redeemedToday = (int) RedemptionLog::query()
            ->where('date', $todayStr)
            ->when($propertyIds, fn($q) => $q->whereHas('guestVoucher', fn($q) => $q->whereIn('property_id', $propertyIds)))
            ->sum('pax_used');

        $remainingToday = max(0, $totalQuotaToday - $redeemedToday);

        $topFacilities = RedemptionLog::query()
            ->select('facility_templates.name as facility_name', DB::raw('SUM(redemption_logs.pax_used) as total_pax'))
            ->join('facility_templates', 'facility_templates.id', '=', 'redemption_logs.facility_template_id')
            ->when($propertyIds, fn($q) => $q->whereIn('facility_templates.property_id', $propertyIds))
            ->groupBy('facility_templates.id', 'facility_templates.name')
            ->orderByDesc('total_pax')
            ->limit(5)
            ->get();

        $outletActivity = RedemptionLog::query()
            ->with(['guest', 'facilityTemplate', 'outlet', 'user'])
            ->when($propertyIds, fn($q) => $q->whereHas('guestVoucher', fn($q) => $q->whereIn('property_id', $propertyIds)))
            ->latest()
            ->limit(10)
            ->get();

        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $sentToday = \App\Models\DeliveryLog::query()
            ->where('delivery_status', 'sent')
            ->whereBetween('updated_at', [$todayStart, $todayEnd])
            ->count();

        $failedDeliveries = \App\Models\DeliveryLog::query()
            ->where('delivery_status', 'failed')
            ->count();

        $pendingDeliveries = \App\Models\DeliveryLog::query()
            ->where('delivery_status', 'pending')
            ->count();

        $totalCompleted = \App\Models\DeliveryLog::query()
            ->whereIn('delivery_status', ['sent', 'failed'])
            ->count();

        $successRate = 0;
        if ($totalCompleted > 0) {
            $totalSent = \App\Models\DeliveryLog::query()->where('delivery_status', 'sent')->count();
            $successRate = round(($totalSent / $totalCompleted) * 100, 1);
        }

        return view('dashboard', [
            'totalGuests' => $totalGuests,
            'activeGuests' => $activeGuests,
            'bookingCount' => $bookingCount,
            'redeemedToday' => $redeemedToday,
            'remainingToday' => $remainingToday,
            'topFacilities' => $topFacilities,
            'outletActivity' => $outletActivity,
            'sentToday' => $sentToday,
            'failedDeliveries' => $failedDeliveries,
            'pendingDeliveries' => $pendingDeliveries,
            'successRate' => $successRate,
        ]);
    }
}
