<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliverySettingsController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('delivery_settings.manage'), 403);

        $settings = [
            'delivery_method' => Setting::get('delivery.delivery_method', 'qr_image'),
            'automatic_enabled' => Setting::get('delivery.automatic_enabled', '1'),
            'scheduled_enabled' => Setting::get('delivery.scheduled_enabled', '0'),
            'default_time' => Setting::get('delivery.default_time', '08:00'),
            'timezone' => Setting::get('delivery.timezone', 'Asia/Jakarta'),
            'whatsapp_provider' => Setting::get('delivery.whatsapp_provider', 'Fonnte'),
            'fonnte_token' => Setting::get('delivery.fonnte_token', 'GpMC1EMdd5nHp9EWboyy'),
            'message_template' => Setting::get(
                'delivery.message_template',
                "Halo {guest_name},\n\nVoucher Digital Anda telah aktif.\n\nRoom:\n{room_code}\n\nTotal Pax:\n{total_pax}\n\nSilakan tunjukkan QR berikut saat menggunakan fasilitas resort.\n\nTerima kasih."
            ),
        ];

        return view('settings.delivery', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('delivery_settings.manage'), 403);

        $validated = $request->validate([
            'delivery_method' => ['required', 'in:qr_image,public_link'],
            'automatic_enabled' => ['required', 'in:0,1'],
            'scheduled_enabled' => ['required', 'in:0,1'],
            'default_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'timezone' => ['required', 'string', 'max:100'],
            'whatsapp_provider' => ['required', 'string', 'max:50'],
            'fonnte_token' => ['nullable', 'string', 'max:255'],
            'message_template' => ['required', 'string'],
        ]);

        Setting::set('delivery.delivery_method', $validated['delivery_method']);
        Setting::set('delivery.automatic_enabled', $validated['automatic_enabled']);
        Setting::set('delivery.scheduled_enabled', $validated['scheduled_enabled']);
        Setting::set('delivery.default_time', $validated['default_time']);
        Setting::set('delivery.timezone', $validated['timezone']);
        Setting::set('delivery.whatsapp_provider', $validated['whatsapp_provider']);
        Setting::set('delivery.fonnte_token', $validated['fonnte_token'] ?? '');
        Setting::set('delivery.message_template', $validated['message_template']);

        return back()->with('success', 'Voucher delivery settings updated successfully.');
    }
}
