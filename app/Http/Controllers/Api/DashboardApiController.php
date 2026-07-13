<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\RedemptionLog;
use App\Services\StayQuotaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends ApiController
{
    public function __construct(
        private readonly StayQuotaService $quota,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorizePermission('bookings.view');

        $today = Carbon::today();
        $timezone = 'UTC';

        $totalGuests = Guest::query()->count();
        $activeGuests = Booking::query()->where('status', 'checked_in')->count();
        $totalBookings = Booking::query()->count();

        $todayQuota = $this->quota->getTodayQuota($timezone);
        $todayUsed = $this->quota->getTodayUsedQuota($timezone);
        $remainingQuota = max(0, $todayQuota - $todayUsed);

        $popularFacilities = RedemptionLog::query()
            ->selectRaw('facility_template_id, SUM(pax_used) as total_pax')
            ->where('date', $today->toDateString())
            ->with('facilityTemplate')
            ->groupBy('facility_template_id')
            ->orderByDesc('total_pax')
            ->limit(5)
            ->get()
            ->map(fn($log) => [
                'name' => $log->facilityTemplate->name,
                'total_pax' => (int) $log->total_pax,
            ]);

        $outletActivity = RedemptionLog::query()
            ->selectRaw('outlet_id, SUM(pax_used) as total_pax, COUNT(*) as total_redemptions')
            ->where('date', $today->toDateString())
            ->with('outlet')
            ->groupBy('outlet_id')
            ->orderByDesc('total_pax')
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'outlet' => $log->outlet?->name ?? 'N/A',
                'total_pax' => (int) $log->total_pax,
                'total_redemptions' => (int) $log->total_redemptions,
            ]);

        return $this->respond([
            'total_guests' => $totalGuests,
            'active_guests' => $activeGuests,
            'total_bookings' => $totalBookings,
            'today_quota' => $todayQuota,
            'today_used' => $todayUsed,
            'remaining_quota' => $remainingQuota,
            'popular_facilities' => $popularFacilities,
            'outlet_activity' => $outletActivity,
        ]);
    }
}
