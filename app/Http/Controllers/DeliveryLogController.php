<?php

namespace App\Http\Controllers;

use App\Models\DeliveryLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeliveryLogController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('delivery_logs.view'), 403);

        $logs = DeliveryLog::query()
            ->with(['booking.guest'])
            ->latest()
            ->paginate(20);

        return view('reports.delivery_logs', compact('logs'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('reports.export'), 403);

        $format = $request->input('format', 'xlsx');

        $logs = DeliveryLog::query()
            ->with(['booking.guest', 'guest'])
            ->latest()
            ->limit(10000)
            ->get();

        $filename = 'delivery-logs-' . now()->format('Y-m-d-His');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DeliveryLogExport($logs),
            "{$filename}.{$format}",
            $this->getExcelType($format)
        );
    }

}
