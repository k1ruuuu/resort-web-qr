<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DailyVoucher;
use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports): View
    {
        return view('dashboard', [
            'bookingCount' => Booking::query()->count(),
            'activeVouchers' => DailyVoucher::query()->where('status', 'active')->count(),
            'voucherStats' => $reports->voucherStatusCounts(null),
        ]);
    }
}
