<?php

namespace App\Http\Controllers;

use App\Exports\RedemptionReportExport;
use App\Models\RedemptionLog;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $from = Carbon::parse($request->input('from', now()->subDays(7)->toDateString()));
        $to = Carbon::parse($request->input('to', now()->toDateString()));

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'redemptions' => $reports->redemptionSummary(
                $request->integer('property_id') ?: null,
                $from,
                $to,
            ),
            'voucherStats' => $reports->voucherStatusCounts($request->integer('property_id') ?: null),
        ]);
    }

    public function exportRedemptions(Request $request)
    {
        abort_unless(auth()->user()?->can('reports.export'), 403);

        $format = $request->input('format', 'xlsx'); // xlsx, xls, csv
        
        $from = Carbon::parse($request->input('from', now()->subDays(7)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $query = RedemptionLog::query()
            ->with(['guest', 'booking.room', 'facilityTemplate', 'outlet', 'user'])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc');

        if ($request->filled('property_id')) {
            $query->whereHas('booking', function ($q) use ($request) {
                $q->where('property_id', $request->property_id);
            });
        }

        if ($request->filled('facility_id')) {
            $query->where('facility_template_id', $request->facility_id);
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        $redemptions = $query->get();

        $filters = $request->only(['from', 'to', 'property_id', 'facility_id', 'outlet_id']);

        $filename = 'redemption-report-' . now()->format('Y-m-d-His');

        return Excel::download(
            new RedemptionReportExport($redemptions, $filters),
            "{$filename}.{$format}",
            $this->getExcelType($format)
        );
    }

    private function getExcelType(string $format): string
    {
        return match($format) {
            'csv' => \Maatwebsite\Excel\Excel::CSV,
            'xls' => \Maatwebsite\Excel\Excel::XLS,
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            default => \Maatwebsite\Excel\Excel::XLSX,
        };
    }
}
