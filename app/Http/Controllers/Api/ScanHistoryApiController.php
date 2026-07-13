<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\QrScanLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanHistoryApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('reports.view');

        $query = QrScanLog::query()
            ->with(['guestVoucher', 'outlet', 'user'])
            ->latest('scanned_at');

        if ($request->filled('search')) {
            $search = preg_replace('/[^\w\s@.\-+]/', '', trim($request->string('search')));
            if (strlen($search) > 0) {
                $query->where(function ($q) use ($search) {
                    $q->where('qr_code', 'like', "%{$search}%")
                        ->orWhereHas('guestVoucher', fn($q) => $q->where('guest_name', 'like', "%{$search}%"));
                });
            }
        }

        if ($request->filled('scan_result')) {
            $query->where('scan_result', $request->string('scan_result'));
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->integer('outlet_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('scanned_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('scanned_at', '<=', $request->string('date_to'));
        }

        return $this->respondPaginated($query->paginate($request->integer('per_page', 20)));
    }
}
