<?php

namespace App\Services;

use App\Models\GuestVoucher;
use App\Models\RedemptionLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function redemptionSummary(?int $propertyId, Carbon $from, Carbon $to): Collection
    {
        return RedemptionLog::query()
            ->select([
                'facility_templates.name as facility_name',
                DB::raw('COUNT(redemption_logs.id) as redemption_count'),
                DB::raw('SUM(redemption_logs.pax_used) as total_pax'),
            ])
            ->join('guest_vouchers', 'guest_vouchers.id', '=', 'redemption_logs.guest_voucher_id')
            ->join('bookings', 'bookings.id', '=', 'guest_vouchers.booking_id')
            ->join('facility_templates', 'facility_templates.id', '=', 'redemption_logs.facility_template_id')
            ->when($propertyId, fn ($q) => $q->where('bookings.property_id', $propertyId))
            ->whereBetween('redemption_logs.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('facility_templates.id', 'facility_templates.name')
            ->get();
    }

    public function voucherStatusCounts(?int $propertyId): Collection
    {
        return GuestVoucher::query()
            ->select(['status', DB::raw('COUNT(*) as total')])
            ->when($propertyId, function ($q) use ($propertyId) {
                $q->whereHas('booking', fn ($b) => $b->where('property_id', $propertyId));
            })
            ->groupBy('status')
            ->get();
    }
}
