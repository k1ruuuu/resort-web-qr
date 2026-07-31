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
            $search = trim($request->string('search'));
            $search = str_replace(['%', '_'], ['\%', '\_'], $search);
            if (strlen($search) > 0) {
                $query->whereHas('guestVoucher', fn($q) => $q->where('guest_name', 'like', "%{$search}%"));
            }
        }

        if ($request->filled('scan_result')) {
            $validResults = ['success', 'not_found', 'voucher_not_active', 'booking_not_checked_in', 'outside_stay_period', 'invalid_outlet', 'facility_not_linked', 'invalid_date', 'quota_exceeded', 'lock_failed', 'rate_limit_exceeded', 'validation_error', 'system_error'];
            $result = $request->string('scan_result');
            if (in_array($result, $validResults)) {
                $query->where('scan_result', $result);
            }
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
