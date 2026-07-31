<?php

namespace App\Services;

use App\Models\GuestVoucher;
use App\Models\RedemptionLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function resolvePeriod(Request $request): array
    {
        $filterType = $request->input('filter_type', 'date_range');

        if ($filterType === 'month') {
            $month = max(1, min(12, $request->integer('month') ?: now()->month));
            $year = $request->integer('year') ?: now()->year;
            $from = Carbon::create($year, $month, 1)->startOfDay();
            $to = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        } elseif ($filterType === 'year') {
            $year = $request->integer('year') ?: now()->year;
            $from = Carbon::create($year, 1, 1)->startOfDay();
            $to = Carbon::create($year, 12, 31)->endOfDay();
        } else {
            $filterType = 'date_range';
            $from = Carbon::parse($request->input('from', now()->subDays(7)->toDateString()))->startOfDay();
            $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [
            'from' => $from,
            'to' => $to,
            'filterType' => $filterType,
            'month' => $filterType === 'month' ? ($request->integer('month') ?: now()->month) : null,
            'year' => $request->integer('year') ?: ($filterType === 'year' ? now()->year : null),
        ];
    }

    public function periodLabel(Carbon $from, Carbon $to, string $filterType): string
    {
        return match ($filterType) {
            'month' => $from->format('F Y'),
            'year' => $from->format('Y'),
            default => $from->toDateString() . ' to ' . $to->toDateString(),
        };
    }

    public function redemptionSummary(?int $propertyId, Carbon $from, Carbon $to): Collection
    {
        return $this->baseRedemptionQuery($propertyId, $from, $to)
            ->select([
                'facility_templates.name as facility_name',
                DB::raw('COUNT(redemption_logs.id) as redemption_count'),
                DB::raw('SUM(redemption_logs.pax_used) as total_pax'),
            ])
            ->join('facility_templates', 'facility_templates.id', '=', 'redemption_logs.facility_template_id')
            ->groupBy('facility_templates.id', 'facility_templates.name')
            ->orderByDesc('total_pax')
            ->get();
    }

    public function redemptionByOutlet(?int $propertyId, Carbon $from, Carbon $to): Collection
    {
        return $this->baseRedemptionQuery($propertyId, $from, $to)
            ->select([
                'outlets.name as outlet_name',
                'facility_templates.name as facility_name',
                DB::raw('COUNT(redemption_logs.id) as redemption_count'),
                DB::raw('SUM(redemption_logs.pax_used) as total_pax'),
            ])
            ->join('outlets', 'outlets.id', '=', 'redemption_logs.outlet_id')
            ->join('facility_templates', 'facility_templates.id', '=', 'redemption_logs.facility_template_id')
            ->groupBy('outlets.id', 'outlets.name', 'facility_templates.id', 'facility_templates.name')
            ->orderByDesc('total_pax')
            ->get();
    }

    public function dailyRedemptionTrend(?int $propertyId, Carbon $from, Carbon $to): Collection
    {
        return $this->baseRedemptionQuery($propertyId, $from, $to)
            ->select([
                'redemption_logs.date',
                DB::raw('COUNT(redemption_logs.id) as redemption_count'),
                DB::raw('SUM(redemption_logs.pax_used) as total_pax'),
            ])
            ->groupBy('redemption_logs.date')
            ->orderBy('redemption_logs.date')
            ->get();
    }

    public function overviewStats(?int $propertyId, Carbon $from, Carbon $to): object
    {
        $query = $this->baseRedemptionQuery($propertyId, $from, $to);

        $totals = (clone $query)
            ->select([
                DB::raw('COUNT(redemption_logs.id) as total_redemptions'),
                DB::raw('COALESCE(SUM(redemption_logs.pax_used), 0) as total_pax'),
                DB::raw('COUNT(DISTINCT redemption_logs.guest_id) as unique_guests'),
            ])
            ->first();

        $totalRedemptions = (int) ($totals->total_redemptions ?? 0);
        $totalPax = (int) ($totals->total_pax ?? 0);

        return (object) [
            'total_redemptions' => $totalRedemptions,
            'total_pax' => $totalPax,
            'unique_guests' => (int) ($totals->unique_guests ?? 0),
            'avg_pax_per_redemption' => $totalRedemptions > 0 ? round($totalPax / $totalRedemptions, 1) : 0,
            'active_days' => $this->dailyRedemptionTrend($propertyId, $from, $to)->count(),
        ];
    }

    public function recentRedemptions(?int $propertyId, Carbon $from, Carbon $to, int $limit = 15): Collection
    {
        return $this->redemptionDetailsQuery($propertyId, $from, $to)
            ->limit($limit)
            ->get();
    }

    public function redemptionDetails(?int $propertyId, Carbon $from, Carbon $to, ?int $facilityId = null, ?int $outletId = null): Collection
    {
        $query = $this->redemptionDetailsQuery($propertyId, $from, $to);

        if ($facilityId) {
            $query->where('redemption_logs.facility_template_id', $facilityId);
        }

        if ($outletId) {
            $query->where('redemption_logs.outlet_id', $outletId);
        }

        return $query->get();
    }

    public function voucherStatusCounts(?int $propertyId): Collection
    {
        return GuestVoucher::query()
            ->select(['status', DB::raw('COUNT(*) as total')])
            ->when($propertyId, function ($q) use ($propertyId) {
                $q->whereHas('booking', fn ($b) => $b->where('property_id', $propertyId));
            })
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();
    }

    protected function baseRedemptionQuery(?int $propertyId, Carbon $from, Carbon $to)
    {
        return RedemptionLog::query()
            ->join('guest_vouchers', 'guest_vouchers.id', '=', 'redemption_logs.guest_voucher_id')
            ->join('bookings', 'bookings.id', '=', 'guest_vouchers.booking_id')
            ->when($propertyId, fn ($q) => $q->where('bookings.property_id', $propertyId))
            ->whereBetween('redemption_logs.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    protected function redemptionDetailsQuery(?int $propertyId, Carbon $from, Carbon $to)
    {
        return RedemptionLog::query()
            ->with(['guest', 'booking.room', 'facilityTemplate', 'outlet', 'user'])
            ->when($propertyId, function ($q) use ($propertyId) {
                $q->whereHas('booking', fn ($b) => $b->where('property_id', $propertyId));
            })
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc');
    }
}
