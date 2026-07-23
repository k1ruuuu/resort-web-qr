<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private const API_URL = 'https://api.convia.id/api/v1/public/messages/send';

    public function send(string $phone, string $message, ?string $qrUrl): array
    {
        $apiKey = Setting::get('delivery.convia_api_key');

        $originalPhone = $phone;
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        Log::info("Phone number normalization", [
            'original' => $originalPhone,
            'cleaned' => $phone,
            'normalized' => $normalizedPhone,
        ]);

        $phoneFilterMode = Setting::get('delivery.phone_filter_mode', 'global');
        if ($phoneFilterMode === 'indonesian_only') {
            if (!$this->isIndonesianNumber($normalizedPhone)) {
                Log::info("Blocked non-Indonesian number", [
                    'original' => $originalPhone,
                    'normalized' => $normalizedPhone,
                    'mode' => 'indonesian_only'
                ]);
                return [
                    'success' => false,
                    'message' => 'Phone number is not Indonesian. Delivery is restricted to Indonesian numbers only.',
                    'response' => json_encode(['status' => false, 'detail' => 'non_indonesian_number_blocked']),
                ];
            }
        }

        Log::info("Phone filter check passed", [
            'mode' => $phoneFilterMode,
            'phone' => $normalizedPhone,
        ]);

        if (empty($apiKey) || $apiKey === 'MOCK_CONVIA_KEY') {
            Log::info("Simulated WhatsApp send", [
                'phone' => $normalizedPhone,
                'message' => $message,
                'qr_url' => $qrUrl
            ]);
            return [
                'success' => true,
                'message' => 'Simulated message sent successfully (Mock mode).',
                'response' => json_encode(['status' => true, 'detail' => 'mocked']),
            ];
        }

        try {
            $payload = [
                'channel' => 'whatsapp',
                'phone_number' => $normalizedPhone,
            ];

            if ($qrUrl) {
                $payload['message_type'] = 'image';
                $payload['media_url'] = $qrUrl;
                $payload['caption'] = $message;
            } else {
                $payload['message_type'] = 'text';
                $payload['content'] = $message;
            }

            Log::info("Convia Request", [
                'url' => self::API_URL,
                'payload' => $payload,
                'phone_original' => $originalPhone,
                'phone_normalized' => $normalizedPhone,
                'has_image' => !empty($qrUrl)
            ]);

            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])->post(self::API_URL, $payload);

            $body = $response->body();
            $data = $response->json();

            Log::info("Convia Response", [
                'status_code' => $response->status(),
                'response' => $body,
            ]);

            if ($response->successful() && ($data['success'] ?? false) === true) {
                return [
                    'success' => true,
                    'message' => 'Sent successfully',
                    'response' => $body,
                ];
            }

            $errorMsg = $data['error']['message'] ?? $data['message'] ?? 'Convia API rejected request';
            $errorDetail = json_encode($data);

            return [
                'success' => false,
                'message' => $errorMsg,
                'response' => $body,
            ];

        } catch (\Throwable $e) {
            Log::error("Convia Error", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'response' => $e->getMessage(),
            ];
        }
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '62')) {
            $phone = '+' . $phone;
        } elseif (str_starts_with($phone, '8') && strlen($phone) >= 10 && strlen($phone) <= 12) {
            $phone = '+62' . $phone;
        }

        return $phone;
    }

    private function isIndonesianNumber(string $phone): bool
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+62') || str_starts_with($phone, '62') || str_starts_with($phone, '08')) {
            return true;
        }

        if (str_starts_with($phone, '8') && strlen($phone) >= 10 && strlen($phone) <= 13) {
            return true;
        }

        return false;
    }
}
