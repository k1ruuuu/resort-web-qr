<?php

namespace App\Http\Controllers;

use App\Exceptions\VoucherException;
use App\Models\GuestVoucher;
use App\Models\VoucherFacilityExchange;
use App\Services\VoucherExchangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoucherExchangeController extends Controller
{
    public function __construct(
        private readonly VoucherExchangeService $exchanges,
    ) {}

    public function storeExchange(Request $request, GuestVoucher $voucher): RedirectResponse
    {
        $this->authorizeVoucherAccess($voucher, 'vouchers.edit');

        $validated = $request->validate([
            'from_facility_template_id' => ['required', 'integer', 'exists:facility_templates,id'],
            'to_facility_template_id' => ['required', 'integer', 'different:from_facility_template_id', 'exists:facility_templates,id'],
            'exchange_date' => ['required', 'date'],
            'pax' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->exchanges->exchangeDinnerFacility($voucher, $validated, auth()->user());
        } catch (VoucherException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'Penukaran fasilitas Dinner berhasil dicatat.');
    }

    public function destroyExchange(GuestVoucher $voucher, VoucherFacilityExchange $exchange): RedirectResponse
    {
        $this->authorizeVoucherAccess($voucher, 'vouchers.edit');

        if ($exchange->guest_voucher_id !== $voucher->id) {
            abort(404);
        }

        try {
            $this->exchanges->deleteExchange($exchange, auth()->user());
        } catch (VoucherException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Penukaran fasilitas berhasil dibatalkan.');
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
