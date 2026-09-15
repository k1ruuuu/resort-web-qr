<?php

namespace App\Http\Controllers;

use App\Exceptions\VoucherException;
use App\Http\Requests\GenerateVoucherRequest;
use App\Http\Requests\RedeemVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use App\Models\Booking;
use App\Models\FacilityTemplate;
use App\Models\GuestVoucher;
use App\Models\Property;
use App\Models\VoucherFacilityExchange;
use App\Services\QrCodeService;
use App\Services\VoucherService;
use App\Services\VoucherDeliveryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(
        private readonly VoucherService $vouchers,
        private readonly QrCodeService $qr,
        private readonly VoucherDeliveryService $delivery,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('vouchers.view'), 403);

        $query = GuestVoucher::query()
            ->with(['booking.guest', 'booking.room', 'property'])
            ->latest('generated_at');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $search = str_replace(['%', '_'], ['\%', '\_'], $search);
            
            if (strlen($search) > 0) {
                $query->where(function ($q) use ($search) {
                    $q->where('qr_code', 'like', "%{$search}%")
                        ->orWhere('secure_token', 'like', "%{$search}%")
                        ->orWhere('guest_name', 'like', "%{$search}%")
                        ->orWhereHas('booking', function ($q) use ($search) {
                            $q->where('booking_code', 'like', "%{$search}%")
                                ->orWhere('reference', 'like', "%{$search}%")
                                ->orWhereHas('guest', function ($q) use ($search) {
                                    $q->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                });
                        });
                });
            }
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Category filter (temporary vs standard)
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // Property filter
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('generated_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('generated_at', '<=', $request->input('date_to'));
        }

        $vouchers = $query->paginate(20)->withQueryString();

        // Lazy expiry: keep admin list statuses fresh even without cron/scheduler
        foreach ($vouchers as $voucher) {
            if ($voucher->status === \App\Enums\VoucherStatus::Active) {
                $this->vouchers->checkAndExpireIfNeeded($voucher);
            }
        }

        $properties = Property::query()->orderBy('name')->get();
        $facilityTemplates = \App\Models\FacilityTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('property_id');

        return view('vouchers.index', compact('vouchers', 'properties', 'facilityTemplates'));
    }

    public function generate(GenerateVoucherRequest $request): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->can('vouchers.generate'), 403);

        try {
            if ($request->filled('booking_id')) {
                $booking = $this->applyPropertyScope(
                    Booking::query()->with(['property', 'room.roomType', 'bookingFacilities']),
                    'property_id'
                )->findOrFail($request->validated('booking_id'));

                $created = $this->vouchers->generateForBooking($booking);
            } else {
                $created = $this->vouchers->generateTemporaryVoucher($request->validated());
            }
        } catch (VoucherException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => $created], 201);
        }

        return back()->with('success', 'Guest voucher card generated.');
    }

    public function show(GuestVoucher $voucher): View
    {
        $this->authorizeVoucherAccess($voucher, 'vouchers.view');

        $voucher->load([
            'booking.guest',
            'booking.room',
            'booking.bookingFacilities.facilityTemplate',
            'property',
            'facilityExchanges.fromFacilityTemplate',
            'facilityExchanges.toFacilityTemplate',
            'facilityExchanges.user',
        ]);

        $this->vouchers->checkAndExpireIfNeeded($voucher);

        $propertyId = $voucher->property_id ?? $voucher->booking?->property_id;

        $facilityTemplates = FacilityTemplate::query()
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $exchangeableFacilities = FacilityTemplate::query()
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->whereIn('code', VoucherFacilityExchange::ALLOWED_EXCHANGE_CODES)
            ->orderBy('sort_order')
            ->get();

        $todayStatuses = $voucher->getFacilityStatuses();

        $facilityExchanges = $voucher->facilityExchanges()
            ->with(['fromFacilityTemplate', 'toFacilityTemplate', 'user'])
            ->latest('exchange_date')
            ->latest('id')
            ->get();

        return view('vouchers.show', [
            'voucher' => $voucher,
            'qrImageUrl' => $this->qr->adminImageUrl($voucher),
            'facilityTemplates' => $facilityTemplates,
            'exchangeableFacilities' => $exchangeableFacilities,
            'todayStatuses' => $todayStatuses,
            'facilityExchanges' => $facilityExchanges,
        ]);
    }

    public function edit(GuestVoucher $voucher): View
    {
        $this->authorizeVoucherAccess($voucher, 'vouchers.edit');

        $voucher->load(['booking.guest', 'booking.room', 'booking.bookingFacilities.facilityTemplate', 'property']);

        $facilityTemplates = FacilityTemplate::query()
            ->where('property_id', $voucher->property_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $currentFacilityIds = $voucher->facility_template_id
            ? array_map('intval', explode(',', $voucher->facility_template_id))
            : ($voucher->booking ? $voucher->booking->bookingFacilities->pluck('facility_template_id')->all() : []);

        return view('vouchers.edit', [
            'voucher' => $voucher,
            'facilityTemplates' => $facilityTemplates,
            'currentFacilityIds' => $currentFacilityIds,
        ]);
    }

    public function update(UpdateVoucherRequest $request, GuestVoucher $voucher): RedirectResponse
    {
        $this->authorizeVoucherAccess($voucher, 'vouchers.edit');

        try {
            $this->vouchers->updateVoucher($voucher, $request->validated());
        } catch (VoucherException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('vouchers.index')
            ->with('success', 'Voucher facilities updated successfully.');
    }

    public function storeExchange(Request $request, GuestVoucher $voucher): RedirectResponse
    {
        return app(VoucherExchangeController::class)->storeExchange($request, $voucher);
    }

    public function destroyExchange(GuestVoucher $voucher, VoucherFacilityExchange $exchange): RedirectResponse
    {
        return app(VoucherExchangeController::class)->destroyExchange($voucher, $exchange);
    }

    public function redeemForm(): View
    {
        return app(VoucherScanController::class)->redeemForm();
    }

    public function scanForm(): View
    {
        return app(VoucherScanController::class)->scanForm();
    }

    public function verifyScannedCode(Request $request): JsonResponse
    {
        return app(VoucherScanController::class)->verifyScannedCode($request);
    }

    public function processScannedCode(RedeemVoucherRequest $request): JsonResponse
    {
        return app(VoucherScanController::class)->processScannedCode($request);
    }

    public function redeem(RedeemVoucherRequest $request): RedirectResponse|JsonResponse
    {
        return app(VoucherScanController::class)->redeem($request);
    }

    public function publicShow(string $token): View
    {
        return app(VoucherPublicController::class)->publicShow($token);
    }

    public function qrImage(GuestVoucher $voucher): Response
    {
        $this->authorizeVoucherAccess($voucher, 'vouchers.view');

        return $this->qr->templateResponse($this->qr->payloadForVoucher($voucher));
    }

    public function qrImagePublic(string $token): Response
    {
        return app(VoucherPublicController::class)->qrImagePublic($token);
    }

    public function resend(Booking $booking): RedirectResponse
    {
        abort_unless(auth()->user()?->can('vouchers.resend'), 403);
        $this->authorizePropertyAccess($booking);

        abort_unless($booking->guestVoucher, 404);

        try {
            $this->delivery->sendManual($booking);
        } catch (\Throwable $e) {
            \Log::error('Voucher resend failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to send WhatsApp message. Reference #' . substr(md5((string) now()), 0, 8));
        }

        return back()->with('success', 'Stay pass sent via WhatsApp successfully.');
    }

    public function resendVoucher(GuestVoucher $voucher): RedirectResponse
    {
        abort_unless(auth()->user()?->can('vouchers.resend'), 403);

        $this->authorizeVoucherAccess($voucher, 'vouchers.resend');

        try {
            $this->delivery->sendVoucherImmediate($voucher);
        } catch (\Throwable $e) {
            \Log::error('Voucher resend failed', ['voucher_id' => $voucher->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to send WhatsApp message. Reference #' . substr(md5((string) now()), 0, 8));
        }

        return back()->with('success', 'Voucher sent via WhatsApp successfully.');
    }

    private function authorizeVoucherAccess(GuestVoucher $voucher, string $permission): void
    {
        $user = auth()->user();
        abort_unless($user?->can($permission), 403);

        if (!$user->hasRole('super-admin') && $voucher->property_id) {
            $allowed = $user->properties()
                ->where('property_id', $voucher->property_id)
                ->exists();

            abort_unless($allowed, 403);
        }
    }
}
