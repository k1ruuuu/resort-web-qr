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
            'convia_api_key' => \App\Models\Setting::get('delivery.convia_api_key', ''),
            'message_template' => \App\Models\Setting::get('delivery.message_template', ''),
            'whatsapp_active' => (bool) \App\Models\Setting::get('delivery.whatsapp_active', false),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission('delivery_settings.manage');

        $data = $request->validate([
            'convia_api_key' => ['nullable', 'string'],
            'message_template' => ['nullable', 'string'],
        ]);

        if (isset($data['convia_api_key'])) {
            \App\Models\Setting::set('delivery.convia_api_key', $data['convia_api_key']);
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
