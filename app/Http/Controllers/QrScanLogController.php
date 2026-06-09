<?php

namespace App\Http\Controllers;

use App\Models\QrScanLog;
use Illuminate\Http\Request;

class QrScanLogController extends Controller
{
    public function index(Request $request)
    {
        $query = QrScanLog::query()
            ->with(['guestVoucher.guest', 'guestVoucher.booking.room', 'outlet', 'user'])
            ->orderBy('scanned_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('qr_code', 'like', "%{$search}%")
                    ->orWhereHas('guestVoucher.guest', function ($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%");
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

        return view('reports.scan-history', compact('logs', 'outlets'));
    }
}
