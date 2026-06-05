<?php

namespace App\Http\Controllers;

use App\Models\DeliveryLog;
use Illuminate\View\View;

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
}
