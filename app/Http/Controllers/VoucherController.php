<?php

namespace App\Http\Controllers;

use App\Exceptions\VoucherException;
use App\Http\Requests\GenerateVoucherRequest;
use App\Http\Requests\RedeemVoucherRequest;
use App\Models\Booking;
use App\Models\DailyVoucher;
use App\Models\Outlet;
use App\Services\QrCodeService;
use App\Services\VoucherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(
        private readonly VoucherService $vouchers,
        private readonly QrCodeService $qr,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('vouchers.view'), 403);

        $vouchers = DailyVoucher::query()
            ->with(['booking.guest', 'facilityTemplate'])
            ->latest('generated_at')
            ->paginate(20);

        return view('vouchers.index', compact('vouchers'));
    }

    public function generate(GenerateVoucherRequest $request): RedirectResponse|JsonResponse
    {
        $booking = Booking::query()
            ->with(['property', 'room.roomType', 'bookingFacilities'])
            ->findOrFail($request->validated('booking_id'));
        $date = $request->filled('valid_date')
            ? Carbon::parse($request->validated('valid_date'))
            : null;

        try {
            $created = $this->vouchers->generateForBooking($booking, $date);
        } catch (VoucherException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => $created], 201);
        }

        return back()->with('success', "{$created->count()} voucher(s) generated.");
    }

    public function show(DailyVoucher $voucher): View
    {
        abort_unless(auth()->user()?->can('vouchers.view'), 403);

        $voucher->load(['booking.guest', 'facilityTemplate']);

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

    public function processScannedCode(RedeemVoucherRequest $request): JsonResponse
    {
        $outlet = Outlet::query()->findOrFail($request->validated('outlet_id'));

        try {
            $voucher = $this->vouchers->redeem(
                $request->validated('qr_code'),
                $outlet,
                $request->user(),
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
            'message' => 'Voucher redeemed successfully!',
            'data' => $voucher->load(['booking.guest', 'facilityTemplate'])->toArray(),
        ]);
    }

    public function redeem(RedeemVoucherRequest $request): RedirectResponse|JsonResponse
    {
        $outlet = Outlet::query()->findOrFail($request->validated('outlet_id'));

        try {
            $voucher = $this->vouchers->redeem(
                $request->validated('qr_code'),
                $outlet,
                $request->user(),
                (int) ($request->validated('pax_used') ?? 1),
            );
        } catch (VoucherException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => $voucher]);
        }

        return back()->with('success', 'Voucher redeemed successfully.');
    }

    public function publicShow(string $token): View
    {
        $voucher = $this->findByPublicToken($token);
        $voucher->load(['booking.guest', 'facilityTemplate']);

        return view('vouchers.public', [
            'voucher' => $voucher,
            'qrImageUrl' => $this->qr->imageUrl($voucher),
        ]);
    }

    public function qrImage(DailyVoucher $voucher): Response
    {
        abort_unless(auth()->user()?->can('vouchers.view'), 403);

        return $this->qr->svgResponse($this->qr->payloadForVoucher($voucher));
    }

    public function qrImagePublic(string $token): Response
    {
        $voucher = $this->findByPublicToken($token);

        return $this->qr->svgResponse($this->qr->payloadForVoucher($voucher));
    }

    private function findByPublicToken(string $token): DailyVoucher
    {
        return DailyVoucher::query()
            ->where('public_token', $token)
            ->orWhere('qr_token', $token)
            ->firstOrFail();
    }
}
