<?php

namespace App\Http\Controllers;

use App\Exceptions\VoucherException;
use App\Http\Requests\RedeemVoucherRequest;
use App\Models\GuestVoucher;
use App\Models\Outlet;
use App\Models\RedemptionLog;
use App\Models\Setting;
use App\Services\FacilityScheduleService;
use App\Services\VoucherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VoucherScanController extends Controller
{
    private readonly FacilityScheduleService $schedules;

    public function __construct(
        private readonly VoucherService $vouchers,
        ?FacilityScheduleService $schedules = null,
    ) {
        $this->schedules = $schedules ?? app(FacilityScheduleService::class);
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
            ->groupBy(fn ($o) => $o->property?->name ?? 'Unassigned');

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
            ->groupBy(fn ($o) => $o->property?->name ?? 'Unassigned');

        return view('vouchers.scan', [
            'outlets' => $outlets,
        ]);
    }

    public function verifyScannedCode(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('vouchers.redeem'), 403);

        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9+\-_]+$/'],
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
        ]);

        $qrCode = $validated['qr_code'];
        if (empty($qrCode)) {
            // SECURITY LOG: Invalid request
            Log::warning('[SECURITY] Voucher verify called without QR code', [
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['success' => false, 'message' => 'QR Code is required.'], 422);
        }

        // SECURITY FIX: Validate QR code format to prevent injection attacks
        if (strlen($qrCode) > 255 || !preg_match('/^[a-zA-Z0-9+\-_]+$/', $qrCode)) {
            Log::warning('[SECURITY] Invalid QR code format', [
                'qr_code_length' => strlen($qrCode),
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid QR code format.'], 422);
        }

        $outletId = $validated['outlet_id'] ?? null;
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
            Log::notice('[SECURITY] Voucher verification failed - not found', [
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
            Log::warning('[SECURITY] Property mismatch in voucher verification', [
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

        $isOneTimeGrace = $voucher->isOneTimeGracePeriodActive();

        // Validate voucher status
        if ($voucher->status !== \App\Enums\VoucherStatus::Active && !$isOneTimeGrace) {
            if ($outlet && $user) {
                $result = $voucher->status === \App\Enums\VoucherStatus::Redeemed ? 'quota_exceeded' : 'voucher_not_active';
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, $result);
            }
            // SECURITY LOG: Inactive voucher access attempt
            Log::info('[SECURITY] Inactive voucher access attempt', [
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

            if ($outlet) {
                $outletFacilityIds = $outlet->facilityTemplates->pluck('id')->toArray();
                $facilityStatuses = $facilityStatuses->filter(fn($f) => in_array($f->facility_template_id, $outletFacilityIds))->values();

                if ($facilityStatuses->isEmpty() || $facilityStatuses->every(fn($f) => $f->quota_remaining <= 0)) {
                    if ($user) {
                        $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'quota_exceeded');
                    }
                    return response()->json([
                        'success' => false,
                        'message' => 'This voucher has no remaining quota for the selected outlet\'s facility.',
                    ], 422);
                }

                $facilityTemplates = $outlet->facilityTemplates->keyBy('id');
                $now = Carbon::now($timezone);
                $allClosed = true;
                $closedHoursLabel = '';

                foreach ($facilityStatuses as $status) {
                    $template = $facilityTemplates->get($status->facility_template_id);
                    $code = $template?->code ?? '';
                    $isOpen = $this->schedules->isWithinOperatingHours($code, $now, $timezone);
                    $hours = $this->schedules->getFormattedOperatingHours($code);
                    $status->is_open_now = $isOpen;
                    $status->operating_hours = $hours;
                    if ($isOpen) {
                        $allClosed = false;
                    } else {
                        $closedHoursLabel = $hours;
                    }
                }

                if ($allClosed && $facilityStatuses->isNotEmpty()) {
                    if ($user) {
                        $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'outside_redemption_hours', $facilityStatuses->first()->facility_template_id);
                    }
                    return response()->json([
                        'success' => false,
                        'message' => "Penukaran voucher diluar jam yang telah ditentukan. (Jam operasional: {$closedHoursLabel})",
                    ], 422);
                }
            }

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
                    'total_pax' => $voucher->additionAppliesOn($today->toDateString())
                        ? ($voucher->pax_limit ?? 1) + ($voucher->addition ?? 0)
                        : ($voucher->pax_limit ?? 1),
                    'facilities' => $facilityStatuses,
                    'auto_select_facility' => $facilityStatuses->count() === 1 ? $facilityStatuses->first()->facility_template_id : null,
                    'history' => $history,
                ]
            ]);
        }

        // Validate booking status
        if ((!$voucher->booking || $voucher->booking->status !== \App\Enums\BookingStatus::CheckIn) && !$isOneTimeGrace) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'booking_not_checked_in');
            }
            return response()->json([
                'success' => false,
                'message' => 'Booking is not currently checked in.'
            ], 422);
        }

        // Validate expiration time (9 PM on checkout date)
        $timezone = $voucher->booking->property?->timezone ?? 'UTC';
        $currentDateTime = Carbon::now($timezone);
        $checkInDate = Carbon::parse($voucher->booking->check_in)->setTimezone($timezone)->startOfDay();
        $checkOutDate = Carbon::parse($voucher->booking->check_out)->setTimezone($timezone)->startOfDay();
        $cutoffTime = Setting::get('maintenance.checkout_cutoff', '12:35');
        $expirationDateTime = $checkOutDate->copy()->setTimeFromTimeString($cutoffTime);

        if ($currentDateTime->lt($checkInDate)) {
            if ($outlet && $user) {
                $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'outside_stay_period');
            }
            return response()->json([
                'success' => false,
                'message' => 'This voucher is not yet valid. Valid from: ' . $checkInDate->format('Y-m-d H:i')
            ], 422);
        }

        if ($currentDateTime->gte($expirationDateTime) && !$isOneTimeGrace) {
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

        if ($outlet) {
            $outletFacilityIds = $outlet->facilityTemplates->pluck('id')->toArray();
            $facilityStatuses = $facilityStatuses->filter(fn($f) => in_array($f->facility_template_id, $outletFacilityIds))->values();

            if ($isOneTimeGrace) {
                $facilityStatuses = $facilityStatuses->filter(fn($f) => $f->is_one_time)->values();
            }

            if ($facilityStatuses->isEmpty() || $facilityStatuses->every(fn($f) => $f->quota_remaining <= 0)) {
                if ($user) {
                    $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'quota_exceeded');
                }
                return response()->json([
                    'success' => false,
                    'message' => $isOneTimeGrace
                        ? 'This voucher has no remaining one-time facility quota during the checkout grace period.'
                        : 'This voucher has no remaining quota for the selected outlet\'s facility.',
                ], 422);
            }

            $facilityTemplates = $outlet->facilityTemplates->keyBy('id');
            $now = Carbon::now($timezone);
            $allClosed = true;
            $closedHoursLabel = '';

            foreach ($facilityStatuses as $status) {
                $template = $facilityTemplates->get($status->facility_template_id);
                $code = $template?->code ?? '';
                $isOpen = $this->schedules->isWithinOperatingHours($code, $now, $timezone);
                $hours = $this->schedules->getFormattedOperatingHours($code);
                $status->is_open_now = $isOpen;
                $status->operating_hours = $hours;
                if ($isOpen) {
                    $allClosed = false;
                } else {
                    $closedHoursLabel = $hours;
                }
            }

            if ($allClosed && $facilityStatuses->isNotEmpty()) {
                if ($user) {
                    $this->vouchers->logScan($qrCode, $voucher, $outlet, $user, 'outside_redemption_hours', $facilityStatuses->first()->facility_template_id);
                }
                return response()->json([
                    'success' => false,
                    'message' => "Penukaran voucher diluar jam yang telah ditentukan. (Jam operasional: {$closedHoursLabel})",
                ], 422);
            }
        }

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
                'guest_name' => $voucher->guest_name ?? $voucher->booking?->guest?->full_name ?? 'N/A',
                'room_code' => $voucher->booking?->room?->code ?? $voucher->booking?->room?->number ?? 'N/A',
                'room_name' => $voucher->booking?->room?->label ?? 'N/A',
                'booking_code' => $voucher->booking->booking_code ?? $voucher->booking->reference,
                'check_in' => $voucher->booking->check_in->format('Y-m-d'),
                'check_out' => $voucher->booking->check_out->format('Y-m-d'),
                'total_pax' => $voucher->additionAppliesOn($today->toDateString())
                    ? $voucher->booking->total_pax + $voucher->booking->extra_beds + ($voucher->addition ?? 0)
                    : $voucher->booking->total_pax + $voucher->booking->extra_beds,
                'facilities' => $facilityStatuses,
                'auto_select_facility' => $facilityStatuses->count() === 1 ? $facilityStatuses->first()->facility_template_id : null,
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
                'guest' => $log->guest?->full_name ?? $log->guestVoucher?->guest_name ?? 'Temporary Guest',
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
}
