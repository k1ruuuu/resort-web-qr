<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('bookings.view'), 403);

        $bookings = Booking::query()
            ->with(['guest', 'property', 'room', 'bookingFacilities.facilityTemplate'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($bookings);
    }
}
