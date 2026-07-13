<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliverySettingsApiController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorizePermission('delivery_settings.manage');

        return $this->respond([
            'fonnte_token' => \App\Models\Setting::get('delivery.fonnte_token', ''),
            'message_template' => \App\Models\Setting::get('delivery.message_template', ''),
            'whatsapp_active' => (bool) \App\Models\Setting::get('delivery.whatsapp_active', false),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission('delivery_settings.manage');

        $data = $request->validate([
            'fonnte_token' => ['nullable', 'string'],
            'message_template' => ['nullable', 'string'],
        ]);

        if (isset($data['fonnte_token'])) {
            \App\Models\Setting::set('delivery.fonnte_token', $data['fonnte_token']);
        }

        if (isset($data['message_template'])) {
            \App\Models\Setting::set('delivery.message_template', $data['message_template']);
        }

        return $this->respondMessage('Delivery settings updated successfully.');
    }

    public function toggleWhatsApp(): JsonResponse
    {
        $this->authorizePermission('delivery_settings.manage');

        $current = (bool) \App\Models\Setting::get('delivery.whatsapp_active', false);
        \App\Models\Setting::set('delivery.whatsapp_active', !$current);

        return $this->respondMessage('WhatsApp delivery ' . (!$current ? 'activated' : 'deactivated') . '.');
    }
}
