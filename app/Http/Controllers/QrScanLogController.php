<?php

namespace App\Http\Controllers;

use App\Exports\ScanHistoryExport;
use App\Models\QrScanLog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class QrScanLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $query = QrScanLog::query()
            ->with([
                'guestVoucher.guest',
                'guestVoucher.booking.guest',
                'guestVoucher.booking.room',
                'guestVoucher.property',
                'outlet.property',
                'user',
                'facilityTemplate'
            ])
            ->orderBy('scanned_at', 'desc');

        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('qr_code', 'like', "%{$search}%")
                    ->orWhere('secure_token', 'like', "%{$search}%")
                    ->orWhereHas('guestVoucher', function ($gv) use ($search) {
                        $gv->where('guest_name', 'like', "%{$search}%")
                            ->orWhereHas('guest', function ($g) use ($search) {
                                $g->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('booking.guest', function ($bg) use ($search) {
                                $bg->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($request->filled('scan_result')) {
            $query->where('scan_result', $request->scan_result);
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('scanned_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('scanned_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->withQueryString();

        $outlets = \App\Models\Outlet::orderBy('name')->get();
        
        $usedStatuses = QrScanLog::select('scan_result')
            ->whereNotNull('scan_result')
            ->distinct()
            ->pluck('scan_result')
            ->toArray();

        return view('reports.scan-history', compact('logs', 'outlets', 'usedStatuses'));
    }

    public function export(Request $request)
    {
        abort_unless(auth()->user()?->can('reports.export'), 403);

        $format = $request->input('format', 'xlsx'); // xlsx, xls, csv

        // Build the same query as index
        $query = QrScanLog::query()
            ->with([
                'guestVoucher.guest',
                'guestVoucher.booking.guest',
                'guestVoucher.booking.room',
                'guestVoucher.property',
                'outlet.property',
                'user',
                'facilityTemplate'
            ])
            ->orderBy('scanned_at', 'desc');

        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('qr_code', 'like', "%{$search}%")
                    ->orWhere('secure_token', 'like', "%{$search}%")
                    ->orWhereHas('guestVoucher', function ($gv) use ($search) {
                        $gv->where('guest_name', 'like', "%{$search}%")
                            ->orWhereHas('guest', function ($g) use ($search) {
                                $g->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('booking.guest', function ($bg) use ($search) {
                                $bg->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($request->filled('scan_result')) {
            $query->where('scan_result', $request->scan_result);
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('scanned_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('scanned_at', '<=', $request->date_to);
        }

        $logs = $query->limit(10000)->get();

        $filters = $request->only(['search', 'scan_result', 'outlet_id', 'date_from', 'date_to']);

        $filename = 'scan-history-' . now()->format('Y-m-d-His');

        return Excel::download(
            new ScanHistoryExport($logs, $filters),
            "{$filename}.{$format}",
            $this->getExcelType($format)
        );
    }

    public function latestScans(Request $request)
    {
        $afterId = (int) $request->input('after_id', 0);

        if ($afterId === 0) {
            $latestId = QrScanLog::max('id') ?? 0;
            return response()->json([
                'latest_id' => $latestId,
                'scans' => [],
            ]);
        }

        $logs = QrScanLog::query()
            ->with([
                'guestVoucher.guest',
                'guestVoucher.booking.guest',
                'guestVoucher.booking.room',
                'guestVoucher.property',
                'outlet.property',
                'user',
                'facilityTemplate'
            ])
            ->where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get();

        $latestId = $logs->isNotEmpty() ? $logs->last()->id : $afterId;

        $scans = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'staff_name' => $log->user?->name ?? 'Staff',
                'outlet_name' => $log->outlet?->name ?? '-',
                'facility_name' => $log->facilityTemplate?->name ?? '-',
                'guest_name' => $log->guest_name,
                'room_label' => $log->room_name,
                'scan_result' => $log->scan_result,
                'scan_result_label' => \Illuminate\Support\Str::headline($log->scan_result ?? 'Scan'),
                'time' => $log->scanned_at_local ? $log->scanned_at_local->format('H:i:s') : now()->format('H:i:s'),
            ];
        });

        return response()->json([
            'latest_id' => $latestId,
            'scans' => $scans,
        ]);
    }
}
