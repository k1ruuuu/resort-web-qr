<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}
