<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\VoucherException;
use App\Http\Controllers\ApiController;
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
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherApiController extends ApiController
{
    public function __construct(
        private readonly VoucherService $vouchers,
        private readonly QrCodeService $qr,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('vouchers.view');

        $query = GuestVoucher::query()
            ->with(['booking.guest', 'booking.room', 'property'])
            ->latest('generated_at');

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $search = str_replace(['%', '_'], ['\%', '\_'], $search);
            if (strlen($search) > 0) {
                $query->where(function ($q) use ($search) {
                    $q->where('qr_code', 'like', "%{$search}%")
                        ->orWhere('secure_token', 'like', "%{$search}%")
                        ->orWhere('guest_name', 'like', "%{$search}%")
                        ->orWhereHas('booking', function ($q) use ($search) {
                            $q->where('booking_code', 'like', "%{$search}%")
                                ->orWhere('reference', 'like', "%{$search}%")
                                ->orWhereHas('guest', fn($q) => $q->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%"));
                        });
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->integer('property_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('generated_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('generated_at', '<=', $request->string('date_to'));
        }

        return $this->respondPaginated($query->paginate($request->integer('per_page', 20)));
    }

    public function show(GuestVoucher $voucher): JsonResponse
    {
        $this->authorizeVoucherAccess($voucher, 'vouchers.view');

        $voucher->load(['booking.guest', 'booking.room', 'booking.bookingFacilities', 'property']);

        return $this->respond($voucher);
    }

    public function generate(GenerateVoucherRequest $request): JsonResponse
    {
        $this->authorizePermission('vouchers.generate');

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
            return $this->respondError($e->getMessage(), $e->getCode() ?: 422);
        }

        return $this->respondCreated($created);
    }

    public function update(UpdateVoucherRequest $request, GuestVoucher $voucher): JsonResponse
    {
        $this->authorizeVoucherAccess($voucher, 'vouchers.generate');

        try {
            $this->vouchers->updateVoucher($voucher, $request->validated());
        } catch (VoucherException $e) {
            return $this->respondError($e->getMessage(), $e->getCode() ?: 422);
        }

        return $this->respond($voucher->fresh());
    }

    public function verify(Request $request): JsonResponse
    {
        $this->authorizePermission('vouchers.redeem');

        $qrCode = $request->input('qr_code');
        if (empty($qrCode)) {
            return $this->respondError('QR Code is required.', 422);
        }

        if (strlen($qrCode) > 255 || !preg_match('/^[a-zA-Z0-9+\-_]+$/', $qrCode)) {
            return $this->respondError('Invalid QR code format.', 422);
        }

        $outletId = $request->input('outlet_id');
        $outlet = $outletId ? Outlet::query()->find($outletId) : null;
        $user = $request->user();

        $voucher = GuestVoucher::query()
            ->where('secure_token', $qrCode)
            ->orWhere('qr_code', $qrCode)
            ->first();

        if (!$voucher) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, null, $outlet, $user, 'not_found');
            }
            return $this->respondError('Voucher not found.', 404);
        }

        $voucher->load(['booking.guest', 'booking.room', 'booking.property', 'booking.bookingFacilities.facilityTemplate', 'property']);

        $this->vouchers->checkAndExpireIfNeeded($voucher);
        $voucher->refresh();

        $voucherPropertyId = $voucher->property_id ?? $voucher->booking?->property_id;
        if ($outlet && $voucherPropertyId && $outlet->property_id !== $voucherPropertyId) {
            if ($user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'invalid_outlet');
            }
            return $this->respondError('This outlet belongs to a different property.', 403);
        }

        if ($voucher->status !== \App\Enums\VoucherStatus::Active) {
            if ($outlet && $user) {
                $result = $voucher->status === \App\Enums\VoucherStatus::Redeemed ? 'quota_exceeded' : 'voucher_not_active';
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, $result);
            }
            return $this->respondError('This voucher is no longer active.', 422);
        }

        if ($voucher->category === 'temporary') {
            $timezone = $voucher->property?->timezone ?? 'UTC';
            $currentDateTime = Carbon::now($timezone);
            $expiresAt = $voucher->expires_at;

            if ($expiresAt && $currentDateTime->gte($expiresAt)) {
                if ($outlet && $user) {
                    $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'outside_stay_period');
                }
                return $this->respondError('This temporary voucher has expired.', 422);
            }

            $today = Carbon::today($timezone);
            $facilityStatuses = $voucher->getFacilityStatuses($today);

            if ($outlet) {
                $outletFacilityIds = $outlet->facilityTemplates->pluck('id')->toArray();
                $facilityStatuses = $facilityStatuses->filter(fn($f) => in_array($f->facility_template_id, $outletFacilityIds))->values();
            }

            $history = RedemptionLog::query()
                ->where('guest_voucher_id', $voucher->id)
                ->with(['facilityTemplate', 'outlet', 'user'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn($log) => [
                    'facility' => $log->facilityTemplate->name,
                    'pax' => $log->pax_used,
                    'outlet' => $log->outlet?->name ?? 'N/A',
                    'staff' => $log->user?->name ?? 'System',
                    'date' => $log->date->format('Y-m-d'),
                    'time' => $log->time,
                ]);

            return $this->respond([
                'voucher_id' => $voucher->id,
                'guest_name' => $voucher->guest_name ?? 'Temporary Guest',
                'room_code' => 'TEMP',
                'room_name' => 'Temporary',
                'booking_code' => null,
                'check_in' => null,
                'check_out' => null,
                'total_pax' => ($voucher->pax_limit ?? 1) + ($voucher->addition ?? 0),
                'facilities' => $facilityStatuses,
                'auto_select_facility' => $facilityStatuses->count() === 1 ? $facilityStatuses->first()->facility_template_id : null,
                'history' => $history,
            ]);
        }

        if (!$voucher->booking || $voucher->booking->status !== \App\Enums\BookingStatus::CheckIn) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'booking_not_checked_in');
            }
            return $this->respondError('Booking is not currently checked in.', 422);
        }

        $timezone = $voucher->booking->property?->timezone ?? 'UTC';
        $currentDateTime = Carbon::now($timezone);
        $checkInDate = Carbon::parse($voucher->booking->check_in)->setTimezone($timezone)->startOfDay();
        $checkOutDate = Carbon::parse($voucher->booking->check_out)->setTimezone($timezone)->startOfDay();
        $expirationDateTime = $checkOutDate->copy()->setTime(21, 0, 0);

        if ($currentDateTime->lt($checkInDate)) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'outside_stay_period');
            }
            return $this->respondError('This voucher is not yet valid. Valid from: ' . $checkInDate->format('Y-m-d H:i'), 422);
        }

        if ($currentDateTime->gte($expirationDateTime)) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'outside_stay_period');
            }
            return $this->respondError('This voucher has expired. It was valid until ' . $expirationDateTime->format('Y-m-d H:i'), 422);
        }

        $today = Carbon::today($timezone);
        $facilityStatuses = $voucher->getFacilityStatuses($today);

        if ($outlet) {
            $outletFacilityIds = $outlet->facilityTemplates->pluck('id')->toArray();
            $facilityStatuses = $facilityStatuses->filter(fn($f) => in_array($f->facility_template_id, $outletFacilityIds))->values();
        }

        $history = RedemptionLog::query()
            ->where('guest_voucher_id', $voucher->id)
            ->with(['facilityTemplate', 'outlet', 'user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'facility' => $log->facilityTemplate->name,
                'pax' => $log->pax_used,
                'outlet' => $log->outlet?->name ?? 'N/A',
                'staff' => $log->user?->name ?? 'System',
                'date' => $log->date->format('Y-m-d'),
                'time' => $log->time,
            ]);

        return $this->respond([
            'voucher_id' => $voucher->id,
            'guest_name' => $voucher->booking->guest?->full_name ?? 'N/A',
            'room_code' => $voucher->booking->room?->code ?? $voucher->booking->room?->number ?? 'N/A',
            'room_name' => $voucher->booking->room?->label ?? 'N/A',
            'booking_code' => $voucher->booking->booking_code ?? $voucher->booking->reference,
            'check_in' => $voucher->booking->check_in->format('Y-m-d'),
            'check_out' => $voucher->booking->check_out->format('Y-m-d'),
            'total_pax' => $voucher->booking->total_pax + $voucher->booking->extra_beds + ($voucher->addition ?? 0),
            'facilities' => $facilityStatuses,
            'auto_select_facility' => $facilityStatuses->count() === 1 ? $facilityStatuses->first()->facility_template_id : null,
            'history' => $history,
        ]);
    }

    public function process(RedeemVoucherRequest $request): JsonResponse
    {
        $this->authorizePermission('vouchers.redeem');

        $outlet = Outlet::query()->with('facilityTemplates')->findOrFail($request->validated('outlet_id'));

        $facilityTemplateId = $request->validated('facility_template_id');
        if (!$facilityTemplateId) {
            $facilities = $outlet->facilityTemplates;
            $facilityTemplateId = $facilities->count() === 1 ? $facilities->first()->id : null;
        }

        if (!$facilityTemplateId) {
            return $this->respondError('Please select a facility for this outlet.', 422);
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
            return $this->respondError($e->getMessage(), $e->getCode() ?: 422);
        }

        return $this->respond([
            'guest' => $log->guest?->full_name ?? $log->guestVoucher?->guest_name ?? 'Temporary Guest',
            'facility' => $log->facilityTemplate->name,
            'pax_used' => $log->pax_used,
            'remaining_quota' => $log->remaining_quota,
            'date' => $log->date->format('Y-m-d'),
            'time' => $log->time,
        ]);
    }

    public function publicShow(string $token): JsonResponse
    {
        $voucher = GuestVoucher::query()
            ->where('secure_token', $token)
            ->with(['booking.guest', 'booking.room', 'property'])
            ->firstOrFail();

        $today = Carbon::today($voucher->property?->timezone ?? $voucher->booking?->property?->timezone ?? 'UTC');
        $facilityStatuses = $voucher->getFacilityStatuses($today);

        return $this->respond([
            'voucher' => $voucher,
            'qr_image_url' => $this->qr->imageUrl($voucher),
            'facility_statuses' => $facilityStatuses,
        ]);
    }

    public function formData(): JsonResponse
    {
        $properties = Property::query()->orderBy('name')->get();
        $facilityTemplates = FacilityTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('property_id');

        return $this->respond([
            'properties' => $properties,
            'facility_templates' => $facilityTemplates,
        ]);
    }

    private function authorizeVoucherAccess(GuestVoucher $voucher, string $permission): void
    {
        $user = request()->user();
        abort_unless($user?->can($permission), 403);

        if (!$user->hasRole('super-admin') && $voucher->property_id) {
            $allowed = $user->properties()
                ->where('property_id', $voucher->property_id)
                ->exists();

            abort_unless($allowed, 403);
        }
    }
}
