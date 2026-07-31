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
            'whatsapp_provider' => \App\Models\Setting::get('delivery.whatsapp_provider', 'Fonnte'),
            'fonnte_api_key' => \App\Models\Setting::get('delivery.fonnte_api_key', ''),
            'whacenter_device_id' => \App\Models\Setting::get('delivery.whacenter_device_id', ''),
            'message_template' => \App\Models\Setting::get('delivery.message_template', ''),
            'whatsapp_active' => (bool) \App\Models\Setting::get('delivery.whatsapp_enabled', false),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission('delivery_settings.manage');

        $data = $request->validate([
            'whatsapp_provider' => ['nullable', 'string', 'in:Fonnte,Whacenter'],
            'fonnte_api_key' => ['nullable', 'string'],
            'whacenter_device_id' => ['nullable', 'string'],
            'message_template' => ['nullable', 'string'],
        ]);

        if (isset($data['whatsapp_provider'])) {
            \App\Models\Setting::set('delivery.whatsapp_provider', $data['whatsapp_provider']);
        }

        if (isset($data['fonnte_api_key'])) {
            \App\Models\Setting::set('delivery.fonnte_api_key', $data['fonnte_api_key']);
        }

        if (isset($data['whacenter_device_id'])) {
            \App\Models\Setting::set('delivery.whacenter_device_id', $data['whacenter_device_id']);
        }

        if (isset($data['message_template'])) {
            \App\Models\Setting::set('delivery.message_template', $data['message_template']);
        }

        return $this->respondMessage('Delivery settings updated successfully.');
    }

    public function toggleWhatsApp(): JsonResponse
    {
        $this->authorizePermission('delivery_settings.manage');

        $current = (bool) \App\Models\Setting::get('delivery.whatsapp_enabled', false);
        \App\Models\Setting::set('delivery.whatsapp_enabled', !$current);

        return $this->respondMessage('WhatsApp delivery ' . (!$current ? 'activated' : 'deactivated') . '.');
    }
}
