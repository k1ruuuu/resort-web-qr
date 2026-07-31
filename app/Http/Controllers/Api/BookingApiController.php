<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\FacilityTemplate;
use App\Models\Property;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingApiController extends ApiController
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly \App\Services\VoucherService $vouchers
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('bookings.view');

        $query = $this->applyPropertyScope(Booking::query())
            ->with(['guest', 'property', 'room'])
            ->latest();

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $search = str_replace(['%', '_'], ['\%', '\_'], $search);
            if (strlen($search) > 0) {
                $query->where(function ($q) use ($search) {
                    $q->where('booking_code', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('guest', fn($q) => $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('room', fn($q) => $q->where('number', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"));
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->integer('property_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('check_in', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('check_out', '<=', $request->string('date_to'));
        }

        return $this->respondPaginated($query->paginate($request->integer('per_page', 20)));
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorizePermission('bookings.view');

        $booking->load(['guest', 'property', 'room', 'bookingFacilities.facilityTemplate', 'guestVoucher']);

        return $this->respond($booking);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $this->authorizePermission('bookings.create');

        $booking = $this->bookings->create(
            $request->safe()->except('facilities'),
            $request->validated('facilities', [])
        );

        return $this->respondCreated($booking);
    }

    public function update(StoreBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorizePermission('bookings.create');

        $booking = $this->bookings->updateBooking(
            $booking,
            $request->safe()->except('facilities'),
            $request->validated('facilities', [])
        );

        return $this->respond($booking);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $this->authorizePermission('bookings.create');

        $booking->delete();

        return $this->respondMessage('Booking deleted successfully.');
    }

    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizePermission('bookings.checkin');

        $facilityTemplateIds = collect($request->input('facility_template_ids', []))
            ->filter(fn($id) => filled($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        try {
            $this->bookings->checkIn($booking, $facilityTemplateIds);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), 422);
        }

        return $this->respondMessage('Guest checked in successfully.');
    }

    public function checkOut(Booking $booking): JsonResponse
    {
        $this->authorizePermission('bookings.checkout');

        $this->bookings->checkOut($booking);

        return $this->respondMessage('Guest checked out successfully.');
    }

    public function formData(): JsonResponse
    {
        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();

        return $this->respond([
            'properties' => $properties,
            'facility_templates' => FacilityTemplate::query()
                ->whereIn('property_id', $properties->pluck('id'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
