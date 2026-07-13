<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\DeliveryLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryLogApiController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('delivery_logs.view');

        $query = DeliveryLog::query()
            ->with(['booking.guest', 'user'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        return $this->respondPaginated($query->paginate($request->integer('per_page', 20)));
    }
}
