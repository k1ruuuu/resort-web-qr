<?php

namespace App\Services;

use App\Models\DailyVoucher;
use App\Models\VoucherUsageLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function redemptionSummary(?int $propertyId, Carbon $from, Carbon $to): Collection
    {
        return VoucherUsageLog::query()
            ->select([
                'facility_templates.name as facility_name',
                DB::raw('COUNT(voucher_usage_logs.id) as redemption_count'),
                DB::raw('SUM(voucher_usage_logs.pax_used) as total_pax'),
            ])
            ->join('daily_vouchers', 'daily_vouchers.id', '=', 'voucher_usage_logs.daily_voucher_id')
            ->join('bookings', 'bookings.id', '=', 'daily_vouchers.booking_id')
            ->join('facility_templates', 'facility_templates.id', '=', 'daily_vouchers.facility_template_id')
            ->when($propertyId, fn ($q) => $q->where('bookings.property_id', $propertyId))
            ->whereBetween('voucher_usage_logs.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('facility_templates.id', 'facility_templates.name')
            ->get();
    }

    public function voucherStatusCounts(?int $propertyId): Collection
    {
        return DailyVoucher::query()
            ->select(['status', DB::raw('COUNT(*) as total')])
            ->when($propertyId, function ($q) use ($propertyId) {
                $q->whereHas('booking', fn ($b) => $b->where('property_id', $propertyId));
            })
            ->groupBy('status')
            ->get();
    }
}
