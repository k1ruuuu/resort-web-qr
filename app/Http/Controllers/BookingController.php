<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\FacilityTemplate;
use App\Models\Guest;
use App\Models\Property;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly \App\Services\VoucherService $vouchers
    ) {}

    public function index(): View
    {
        $this->authorizePermission('bookings.view');

        $query = Booking::query()
            ->with(['guest', 'property', 'room'])
            ->latest();

        // Search filter with SQL injection protection
        if (request()->filled('search')) {
            $search = request('search');
            // SECURITY FIX: Sanitize search input to prevent SQL injection
            $search = preg_replace('/[^\w\s@.\-+]/', '', $search);
            $search = trim($search);
            
            if (strlen($search) > 0) {
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

        $facilityTemplates = FacilityTemplate::query()
            ->where('property_id', $booking->property_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('bookings.show', compact('booking', 'facilityTemplates'));
    }

    public function checkIn(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizePermission('bookings.checkin');

        $facilityTemplateIds = collect($request->input('facility_template_ids', []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->bookings->checkIn($booking, $facilityTemplateIds);

        return back()->with('success', 'Guest checked in.');
    }

    public function checkOut(Booking $booking): RedirectResponse
    {
        $this->authorizePermission('bookings.checkout');

        $this->bookings->checkOut($booking);

        return back()->with('success', 'Guest checked out.');
    }

    public function import(): View
    {
        $this->authorizePermission('bookings.create');

        return view('bookings.import');
    }

    public function processImport(\Illuminate\Http\Request $request): RedirectResponse
    {
        $this->authorizePermission('bookings.create');

        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,xls,xlsx,cvs,txt', 'max:10240'],
        ]);

        try {
            $file = $request->file('file');
            $headingRow = $this->detectHeadingRow($file->getRealPath());

            $import = new \App\Imports\BookingsImport($headingRow);
            \Maatwebsite\Excel\Facades\Excel::import($import, $file);

            // Auto-generate vouchers for checked-in bookings
            $vouchersGenerated = 0;
            $checkedInBookings = $import->getCheckedInBookings();
            
            foreach ($checkedInBookings as $booking) {
                try {
                    // Refresh booking to ensure it's saved
                    $booking->refresh();
                    
                    // Check if voucher already exists
                    if ($booking->guestVoucher) {
                        continue;
                    }
                    
                    // Load necessary relationships
                    $booking->load(['property', 'room.roomType', 'bookingFacilities', 'guest']);
                    
                    // Sync default facilities if none exist
                    if ($booking->bookingFacilities->isEmpty()) {
                        $this->bookings->syncDefaultFacilities($booking);
                        $booking->load('bookingFacilities');
                    }
                    
                    // Generate voucher
                    $this->vouchers->generateForBooking($booking);
                    $vouchersGenerated++;
                } catch (\Exception $e) {
                    \Log::warning("Failed to auto-generate voucher for booking {$booking->id}: " . $e->getMessage());
                }
            }

            $message = "Import completed. {$import->getImported()} bookings imported";
            
            if ($vouchersGenerated > 0) {
                $message .= ", {$vouchersGenerated} QR vouchers auto-generated for checked-in bookings";
            }
            
            if ($import->getSkipped() > 0) {
                $message .= ", {$import->getSkipped()} skipped (duplicates or errors)";
            }

            if (count($import->getFailures()) > 0) {
                $message .= ", " . count($import->getFailures()) . " failed";
                session()->flash('import_failures', $import->getFailures());
            }

            if (count($import->getErrors()) > 0) {
                session()->flash('import_errors', $import->getErrors());
            }

            return redirect()->route('bookings.index')->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorizePermission('bookings.create');

        $export = new \App\Exports\BookingsTemplateExport();
        return \Maatwebsite\Excel\Facades\Excel::download($export, 'bookings-import-template.xlsx');
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

    private function detectHeadingRow(string $filePath): int
    {
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            for ($i = 1; $i <= 10; $i++) {
                $hasHeader = false;
                foreach ($sheet->getRowIterator($i, $i) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $val = strtolower(trim((string)$cell->getValue()));
                        if (in_array($val, [
                            'rsv no', 'reference', 'booking code', 'booking_code',
                            'guest name', 'guest_name', 'guest first name', 'guest_first_name',
                            'room number', 'room_number'
                        ])) {
                            $hasHeader = true;
                            break;
                        }
                    }
                }
                if ($hasHeader) {
                    return $i;
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to detect heading row, using default line 1: ' . $e->getMessage());
        }
        return 1;
    }
}
