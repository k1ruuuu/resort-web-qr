<?php

namespace App\Http\Controllers;

use App\Exports\ReportsSummaryExport;
use App\Models\FacilityTemplate;
use App\Models\Outlet;
use App\Models\Property;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $period = $reports->resolvePeriod($request);
        $from = $period['from'];
        $to = $period['to'];
        $filterType = $period['filterType'];

        $propertyId = $request->integer('property_id') ?: null;
        $facilityId = $request->integer('facility_id') ?: null;
        $outletId = $request->integer('outlet_id') ?: null;

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'filterType' => $filterType,
            'month' => $period['month'] ?? $request->integer('month') ?: now()->month,
            'year' => $period['year'] ?? $request->integer('year') ?: now()->year,
            'periodLabel' => $reports->periodLabel($from, $to, $filterType),
            'propertyId' => $propertyId,
            'facilityId' => $facilityId,
            'outletId' => $outletId,
            'properties' => Property::query()->where('is_active', true)->orderBy('name')->get(),
            'facilities' => FacilityTemplate::query()
                ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'outlets' => Outlet::query()
                ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
                ->when($facilityId, fn ($q) => $q->whereHas('facilityTemplates', fn($q2) => $q2->where('facility_templates.id', $facilityId)))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'overview' => $reports->overviewStats($propertyId, $from, $to),
            'redemptions' => $reports->redemptionSummary($propertyId, $from, $to),
            'redemptionsByOutlet' => $reports->redemptionByOutlet($propertyId, $from, $to),
            'dailyTrend' => $reports->dailyRedemptionTrend($propertyId, $from, $to),
            'recentRedemptions' => $reports->recentRedemptions($propertyId, $from, $to),
            'voucherStats' => $reports->voucherStatusCounts($propertyId),
        ]);
    }

    public function exportRedemptions(Request $request, ReportService $reports): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('reports.export'), 403);

        $format = $request->input('format', 'xlsx');
        $period = $reports->resolvePeriod($request);
        $from = $period['from'];
        $to = $period['to'];

        $propertyId = $request->integer('property_id') ?: null;
        $facilityId = $request->integer('facility_id') ?: null;
        $outletId = $request->integer('outlet_id') ?: null;

        $filters = $request->only(['filter_type', 'from', 'to', 'month', 'year', 'property_id', 'facility_id', 'outlet_id']);

        $filename = 'redemption-report-' . now()->format('Y-m-d-His');

        return Excel::download(
            new ReportsSummaryExport(
                $reports->overviewStats($propertyId, $from, $to),
                $reports->redemptionSummary($propertyId, $from, $to),
                $reports->redemptionByOutlet($propertyId, $from, $to),
                $reports->dailyRedemptionTrend($propertyId, $from, $to),
                $reports->redemptionDetails($propertyId, $from, $to, $facilityId, $outletId),
                $filters,
                $reports->periodLabel($from, $to, $period['filterType']),
            ),
            "{$filename}.{$format}",
            $this->getExcelType($format),
        );
    }

}
