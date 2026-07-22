<?php

namespace App\Http\Controllers;

use App\Exceptions\VoucherException;
use App\Http\Requests\GenerateVoucherRequest;
use App\Http\Requests\RedeemVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use App\Models\Booking;
use App\Models\FacilityTemplate;
use App\Models\GuestVoucher;
use App\Models\Outlet;
use App\Models\Property;
use App\Models\RedemptionLog;
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
                $booking = Booking::query()
                    ->with(['property', 'room.roomType', 'bookingFacilities'])
                    ->findOrFail($request->validated('booking_id'));

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

        $voucher->load(['booking.guest', 'booking.room', 'booking.bookingFacilities']);

        $facilityTemplates = FacilityTemplate::query()
            ->where('property_id', $voucher->property_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('vouchers.show', [
            'voucher' => $voucher,
            'qrImageUrl' => $this->qr->adminImageUrl($voucher),
            'facilityTemplates' => $facilityTemplates,
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
            : [];

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
            $updated = $this->vouchers->updateVoucher($voucher, $request->validated());
        } catch (VoucherException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('vouchers.index')
            ->with('success', 'Voucher facilities updated successfully.');
    }

    public function redeemForm(): View
    {
        abort_unless(auth()->user()?->can('vouchers.redeem'), 403);

        $outlets = Outlet::query()
            ->where('is_active', true)
            ->with(['property', 'facilityTemplates'])
            ->orderBy('property_id')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($o) => $o->property->name);

        return view('vouchers.redeem', [
            'outlets' => $outlets,
        ]);
    }

    public function scanForm(): View
    {
        abort_unless(auth()->user()?->can('vouchers.redeem'), 403);

        $outlets = Outlet::query()
            ->where('is_active', true)
            ->with(['property', 'facilityTemplates'])
            ->orderBy('property_id')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($o) => $o->property->name);

        return view('vouchers.scan', [
            'outlets' => $outlets,
        ]);
    }

    public function verifyScannedCode(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('vouchers.redeem'), 403);

        $qrCode = $request->input('qr_code');
        if (empty($qrCode)) {
            // SECURITY LOG: Invalid request
            \Log::warning('[SECURITY] Voucher verify called without QR code', [
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['success' => false, 'message' => 'QR Code is required.'], 422);
        }

        // SECURITY FIX: Validate QR code format to prevent injection attacks
        if (strlen($qrCode) > 255 || !preg_match('/^[a-zA-Z0-9+\-_]+$/', $qrCode)) {
            \Log::warning('[SECURITY] Invalid QR code format', [
                'qr_code_length' => strlen($qrCode),
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid QR code format.'], 422);
        }

        $outletId = $request->input('outlet_id');
        $outlet = $outletId ? Outlet::query()->find($outletId) : null;
        $user = auth()->user();

        $voucher = GuestVoucher::query()
            ->where('secure_token', $qrCode)
            ->orWhere('qr_code', $qrCode)
            ->first();

        if (!$voucher) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, null, $outlet, $user, 'not_found');
            }
            // SECURITY LOG: Voucher not found (possible enumeration attempt)
            \Log::notice('[SECURITY] Voucher verification failed - not found', [
                'qr_code' => substr($qrCode, 0, 20) . '...',
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'outlet_id' => $outletId,
            ]);
            return response()->json(['success' => false, 'message' => 'Voucher not found.'], 404);
        }

        $voucher->load(['booking.guest', 'booking.room', 'booking.property', 'property']);

        // Auto-expire if passed checkout time
        $this->vouchers->checkAndExpireIfNeeded($voucher);
        $voucher->refresh();

        // Check if outlet matches voucher property
        $voucherPropertyId = $voucher->property_id ?? $voucher->booking?->property_id;
        if ($outlet && $voucherPropertyId && $outlet->property_id !== $voucherPropertyId) {
            if ($user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'invalid_outlet');
            }
            // SECURITY LOG: Property mismatch (possible privilege escalation)
            \Log::warning('[SECURITY] Property mismatch in voucher verification', [
                'voucher_id' => $voucher->id,
                'voucher_property' => $voucherPropertyId,
                'outlet_property' => $outlet->property_id,
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'This outlet belongs to a different property.'
            ], 403);
        }

        // Validate voucher status
        if ($voucher->status !== \App\Enums\VoucherStatus::Active) {
            if ($outlet && $user) {
                $result = $voucher->status === \App\Enums\VoucherStatus::Redeemed ? 'quota_exceeded' : 'voucher_not_active';
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, $result);
            }
            // SECURITY LOG: Inactive voucher access attempt
            \Log::info('[SECURITY] Inactive voucher access attempt', [
                'voucher_id' => $voucher->id,
                'status' => $voucher->status->value,
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'This voucher is no longer active.'
            ], 422);
        }

        if ($voucher->category === 'temporary') {
            $timezone = $voucher->property?->timezone ?? 'UTC';
            $currentDateTime = Carbon::now($timezone);
            $expiresAt = $voucher->expires_at;

            if ($expiresAt && $currentDateTime->gte($expiresAt)) {
                if ($outlet && $user) {
                    $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'outside_stay_period');
                }
                return response()->json([
                    'success' => false,
                    'message' => 'This temporary voucher has expired.'
                ], 422);
            }

            $today = Carbon::today($timezone);
            $facilityStatuses = $voucher->getFacilityStatuses($today);

            $history = RedemptionLog::query()
                ->where('guest_voucher_id', $voucher->id)
                ->with(['facilityTemplate', 'outlet', 'user'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'facility' => $log->facilityTemplate->name,
                        'pax' => $log->pax_used,
                        'outlet' => $log->outlet?->name ?? 'N/A',
                        'staff' => $log->user?->name ?? 'System',
                        'date' => $log->date->format('Y-m-d'),
                        'time' => $log->time,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'voucher_id' => $voucher->id,
                    'guest_name' => $voucher->guest_name ?? 'Temporary Guest',
                    'room_code' => 'TEMP',
                    'room_name' => 'Temporary',
                    'booking_code' => null,
                    'check_in' => null,
                    'check_out' => null,
                    'total_pax' => ($voucher->pax_limit ?? 1) + ($voucher->addition ?? 0),
                    'facilities' => $facilityStatuses,
                    'history' => $history,
                ]
            ]);
        }

        // Validate booking status
        if ($voucher->booking->status !== \App\Enums\BookingStatus::CheckedIn) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'booking_not_checked_in');
            }
            return response()->json([
                'success' => false,
                'message' => 'Booking is not currently checked in.'
            ], 422);
        }

        // Validate expiration time (9 PM on checkout date)
        $timezone = $voucher->booking->property->timezone ?? 'UTC';
        $currentDateTime = Carbon::now($timezone);
        $checkInDate = Carbon::parse($voucher->booking->check_in)->setTimezone($timezone)->startOfDay();
        $checkOutDate = Carbon::parse($voucher->booking->check_out)->setTimezone($timezone)->startOfDay();
        $expirationDateTime = $checkOutDate->copy()->setTime(21, 0, 0);

        if ($currentDateTime->lt($checkInDate)) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'outside_stay_period');
            }
            return response()->json([
                'success' => false,
                'message' => 'This voucher is not yet valid. Valid from: ' . $checkInDate->format('Y-m-d H:i')
            ], 422);
        }

        if ($currentDateTime->gte($expirationDateTime)) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'outside_stay_period');
            }
            return response()->json([
                'success' => false,
                'message' => 'This voucher has expired. It was valid until ' . $expirationDateTime->format('Y-m-d H:i') . ' (' . $timezone . ')'
            ], 422);
        }

        $today = Carbon::today($timezone);
        $facilityStatuses = $voucher->getFacilityStatuses($today);

        $history = RedemptionLog::query()
            ->where('guest_voucher_id', $voucher->id)
            ->with(['facilityTemplate', 'outlet', 'user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'facility' => $log->facilityTemplate->name,
                    'pax' => $log->pax_used,
                    'outlet' => $log->outlet?->name ?? 'N/A',
                    'staff' => $log->user?->name ?? 'System',
                    'date' => $log->date->format('Y-m-d'),
                    'time' => $log->time,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'voucher_id' => $voucher->id,
                'guest_name' => $voucher->booking->guest->full_name,
                'room_code' => $voucher->booking->room?->code ?? $voucher->booking->room?->number ?? 'N/A',
                'room_name' => $voucher->booking->room?->label ?? 'N/A',
                'booking_code' => $voucher->booking->booking_code ?? $voucher->booking->reference,
                'check_in' => $voucher->booking->check_in->format('Y-m-d'),
                'check_out' => $voucher->booking->check_out->format('Y-m-d'),
                'total_pax' => $voucher->booking->total_pax + $voucher->booking->extra_beds + ($voucher->addition ?? 0),
                'facilities' => $facilityStatuses,
                'history' => $history,
            ]
        ]);
    }

    public function processScannedCode(RedeemVoucherRequest $request): JsonResponse
    {
        $outlet = Outlet::query()->with('facilityTemplates')->findOrFail($request->validated('outlet_id'));

        $facilityTemplateId = $request->validated('facility_template_id');
        if (!$facilityTemplateId) {
            $facilities = $outlet->facilityTemplates;
            $facilityTemplateId = $facilities->count() === 1 ? $facilities->first()->id : null;
        }

        if (!$facilityTemplateId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a facility for this outlet.',
            ], 422);
        }

        try {
            $log = $this->vouchers->redeem(
                $request->validated('qr_code'),
                $outlet,
                $request->user(),
                (int) $facilityTemplateId,
                (int) ($request->validated('pax_used') ?? 1),
            );
        } catch (VoucherException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Facility redeemed successfully!',
            'data' => [
                'guest' => $log->guest->full_name,
                'facility' => $log->facilityTemplate->name,
                'pax_used' => $log->pax_used,
                'remaining_quota' => $log->remaining_quota,
                'date' => $log->date->format('Y-m-d'),
                'time' => $log->time,
            ],
        ]);
    }

    public function redeem(RedeemVoucherRequest $request): RedirectResponse|JsonResponse
    {
        $outlet = Outlet::query()->with('facilityTemplates')->findOrFail($request->validated('outlet_id'));

        $facilityTemplateId = $request->validated('facility_template_id');
        if (!$facilityTemplateId) {
            $facilities = $outlet->facilityTemplates;
            $facilityTemplateId = $facilities->count() === 1 ? $facilities->first()->id : null;
        }

        if (!$facilityTemplateId) {
            $errorMessage = 'Please select a facility for this outlet.';
            
            if ($request->expectsJson()) {
                return response()->json(['message' => $errorMessage], 422);
            }

            return back()->with('error', $errorMessage)->withInput();
        }

        try {
            $log = $this->vouchers->redeem(
                $request->validated('qr_code'),
                $outlet,
                $request->user(),
                (int) $facilityTemplateId,
                (int) ($request->validated('pax_used') ?? 1),
            );
        } catch (VoucherException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => $log]);
        }

        return back()->with('success', 'Facility redeemed successfully.');
    }

    public function publicShow(string $token): View
    {
        $voucher = $this->findByPublicToken($token);
        $voucher->load(['booking.guest', 'booking.room', 'property']);
        $today = Carbon::today($voucher->property?->timezone ?? $voucher->booking?->property?->timezone ?? 'UTC');
        $facilityStatuses = $voucher->getFacilityStatuses($today);

        return view('vouchers.public', [
            'voucher' => $voucher,
            'qrImageUrl' => $this->qr->imageUrl($voucher),
            'facilityStatuses' => $facilityStatuses,
        ]);
    }

    public function qrImage(GuestVoucher $voucher): Response
    {
        $this->authorizeVoucherAccess($voucher, 'vouchers.view');

        return $this->qr->svgResponse($this->qr->payloadForVoucher($voucher));
    }

    public function qrImagePublic(string $token): Response
    {
        $voucher = $this->findByPublicToken($token);

        return $this->qr->templateResponse($this->qr->payloadForVoucher($voucher));
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

    private function findByPublicToken(string $token): GuestVoucher
    {
        // SECURITY FIX: Only allow access via secure_token (32-char random)
        // Never allow direct QR code access to prevent enumeration attacks
        return GuestVoucher::query()
            ->where('secure_token', $token)
            ->firstOrFail();
    }

    public function resend(Booking $booking): RedirectResponse
    {
        abort_unless(auth()->user()?->can('vouchers.resend'), 403);

        try {
            $this->delivery->sendManual($booking);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send WhatsApp message: ' . $e->getMessage());
        }

        return back()->with('success', 'Stay pass sent via WhatsApp successfully.');
    }
}
