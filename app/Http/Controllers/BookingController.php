<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\FacilityTemplate;
use App\Models\Guest;
use App\Models\Property;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    public function index(): View
    {
        $this->authorizePermission('bookings.view');

        $query = Booking::query()
            ->with(['guest', 'property', 'room'])
            ->latest();

        // Search filter
        if (request()->filled('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('guest', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('room', function ($q) use ($search) {
                        $q->where('number', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        // Property filter
        if (request()->filled('property_id')) {
            $query->where('property_id', request('property_id'));
        }

        // Date range filter
        if (request()->filled('date_from')) {
            $query->whereDate('check_in', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('check_out', '<=', request('date_to'));
        }

        $bookings = $query->paginate(20)->withQueryString();
        $properties = Property::query()->orderBy('name')->get();

        return view('bookings.index', compact('bookings', 'properties'));
    }

    public function create(): View
    {
        $this->authorizePermission('bookings.create');

        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();

        return view('bookings.create', [
            'properties' => $properties,
            'guests' => Guest::query()->orderBy('last_name')->limit(100)->get(),
            'rooms' => \App\Models\Room::query()->with('property')->orderBy('number')->get(),
            'facilityTemplates' => FacilityTemplate::query()
                ->whereIn('property_id', $properties->pluck('id'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $booking = $this->bookings->create(
            $request->safe()->except('facilities'),
            $request->validated('facilities', [])
        );

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Booking created.');
    }

    public function show(Booking $booking): View
    {
        $this->authorizePermission('bookings.view');

        $booking->load(['guest', 'property', 'room', 'bookingFacilities.facilityTemplate', 'guestVoucher']);

        return view('bookings.show', compact('booking'));
    }

    public function checkIn(Booking $booking): RedirectResponse
    {
        $this->authorizePermission('bookings.checkin');

        $this->bookings->checkIn($booking);

        return back()->with('success', 'Guest checked in.');
    }

    public function checkOut(Booking $booking): RedirectResponse
    {
        $this->authorizePermission('bookings.checkout');

        $this->bookings->checkOut($booking);

        return back()->with('success', 'Guest checked out.');
    }

    public function edit(Booking $booking): View
    {
        $this->authorizePermission('bookings.create');

        $booking->load(['guest', 'property', 'room', 'bookingFacilities.facilityTemplate']);
        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();

        return view('bookings.edit', [
            'booking' => $booking,
            'properties' => $properties,
            'guests' => Guest::query()->orderBy('last_name')->get(),
            'facilityTemplates' => FacilityTemplate::query()
                ->where('property_id', $booking->property_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(StoreBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorizePermission('bookings.create');

        $validated = $request->safe()->except('facilities');
        $booking->update($validated);
        
        if ($request->has('facilities')) {
            $booking->bookingFacilities()->delete();
            foreach ($request->validated('facilities', []) as $facility) {
                $booking->bookingFacilities()->create($facility);
            }
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Booking updated.');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $this->authorizePermission('bookings.create');

        $booking->delete();

        return redirect()->route('bookings.index')->with('success', 'Booking deleted.');
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
