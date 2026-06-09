<?php

namespace App\Http\Controllers;

use App\Exceptions\VoucherException;
use App\Http\Requests\GenerateVoucherRequest;
use App\Http\Requests\RedeemVoucherRequest;
use App\Models\Booking;
use App\Models\GuestVoucher;
use App\Models\Outlet;
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

    public function index(): View
    {
        abort_unless(auth()->user()?->can('vouchers.view'), 403);

        $vouchers = GuestVoucher::query()
            ->with(['booking.guest', 'booking.room'])
            ->latest('generated_at')
            ->paginate(20);

        return view('vouchers.index', compact('vouchers'));
    }

    public function generate(GenerateVoucherRequest $request): RedirectResponse|JsonResponse
    {
        $booking = Booking::query()
            ->with(['property', 'room.roomType', 'bookingFacilities'])
            ->findOrFail($request->validated('booking_id'));

        try {
            $created = $this->vouchers->generateForBooking($booking);
        } catch (VoucherException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => $created], 201);
        }

        return back()->with('success', "Guest voucher card generated.");
    }

    public function show(GuestVoucher $voucher): View
    {
        abort_unless(auth()->user()?->can('vouchers.view'), 403);

        $voucher->load(['booking.guest', 'booking.room']);

        return view('vouchers.show', [
            'voucher' => $voucher,
            'qrImageUrl' => $this->qr->adminImageUrl($voucher),
        ]);
    }

    public function redeemForm(): View
    {
        abort_unless(auth()->user()?->can('vouchers.redeem'), 403);

        return view('vouchers.redeem', [
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function scanForm(): View
    {
        abort_unless(auth()->user()?->can('vouchers.redeem'), 403);

        return view('vouchers.scan', [
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function verifyScannedCode(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('vouchers.redeem'), 403);

        $qrCode = $request->input('qr_code');
        if (empty($qrCode)) {
            return response()->json(['success' => false, 'message' => 'QR Code is required.'], 422);
        }

        $voucher = GuestVoucher::query()
            ->where('secure_token', $qrCode)
            ->orWhere('qr_code', $qrCode)
            ->first();

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher not found.'], 404);
        }

        $voucher->load(['booking.guest', 'booking.room', 'booking.property']);

        // Auto-expire if passed checkout time
        $voucherService = app(\App\Services\VoucherService::class);
        $voucherService->checkAndExpireIfNeeded($voucher);

        // Validate voucher status
        if ($voucher->status !== \App\Enums\VoucherStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'This voucher is no longer active.'
            ], 422);
        }

        // Validate booking status
        if ($voucher->booking->status !== \App\Enums\BookingStatus::CheckedIn) {
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
            return response()->json([
                'success' => false,
                'message' => 'This voucher is not yet valid. Valid from: ' . $checkInDate->format('Y-m-d H:i')
            ], 422);
        }

        if ($currentDateTime->gte($expirationDateTime)) {
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
                'total_pax' => $voucher->booking->total_pax + $voucher->booking->extra_beds,
                'facilities' => $facilityStatuses,
                'history' => $history,
            ]
        ]);
    }

    public function processScannedCode(RedeemVoucherRequest $request): JsonResponse
    {
        $outlet = Outlet::query()->with('facilityTemplate')->findOrFail($request->validated('outlet_id'));

        // Use facility from request if provided, otherwise use outlet's facility
        $facilityTemplateId = $request->validated('facility_template_id') ?? $outlet->facility_template_id;

        if (!$facilityTemplateId) {
            return response()->json([
                'success' => false,
                'message' => 'This outlet is not configured with a facility. Please contact administrator.',
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
        $outlet = Outlet::query()->with('facilityTemplate')->findOrFail($request->validated('outlet_id'));

        // Use facility from request if provided, otherwise use outlet's facility
        $facilityTemplateId = $request->validated('facility_template_id') ?? $outlet->facility_template_id;

        if (!$facilityTemplateId) {
            $errorMessage = 'This outlet is not configured with a facility. Please contact administrator.';
            
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
        $voucher->load(['booking.guest', 'booking.room']);
        $today = Carbon::today($voucher->booking->property->timezone ?? 'UTC');
        $facilityStatuses = $voucher->getFacilityStatuses($today);

        return view('vouchers.public', [
            'voucher' => $voucher,
            'qrImageUrl' => $this->qr->imageUrl($voucher),
            'facilityStatuses' => $facilityStatuses,
        ]);
    }

    public function qrImage(GuestVoucher $voucher): Response
    {
        abort_unless(auth()->user()?->can('vouchers.view'), 403);

        return $this->qr->svgResponse($this->qr->payloadForVoucher($voucher));
    }

    public function qrImagePublic(string $token): Response
    {
        $voucher = $this->findByPublicToken($token);

        return $this->qr->svgResponse($this->qr->payloadForVoucher($voucher));
    }

    private function findByPublicToken(string $token): GuestVoucher
    {
        return GuestVoucher::query()
            ->where('secure_token', $token)
            ->orWhere('qr_code', $token)
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
