<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\VoucherException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateVoucherRequest;
use App\Http\Requests\RedeemVoucherRequest;
use App\Models\Booking;
use App\Models\Outlet;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;

class VoucherApiController extends Controller
{
    public function __construct(private readonly VoucherService $vouchers) {}

    public function generate(GenerateVoucherRequest $request): JsonResponse
    {
        $booking = Booking::query()->findOrFail($request->validated('booking_id'));

        try {
            $created = $this->vouchers->generateForBooking($booking);
        } catch (VoucherException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json(['data' => $created], 201);
    }

    public function redeem(RedeemVoucherRequest $request): JsonResponse
    {
        $outlet = Outlet::query()->findOrFail($request->validated('outlet_id'));

        try {
            $log = $this->vouchers->redeem(
                $request->validated('qr_code'),
                $outlet,
                $request->user(),
                (int) $request->validated('facility_template_id'),
                (int) ($request->validated('pax_used') ?? 1),
            );
        } catch (VoucherException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json(['data' => $log]);
    }
}
