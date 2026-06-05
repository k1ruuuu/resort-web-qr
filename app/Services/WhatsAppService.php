<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function send(string $phone, string $message, ?string $qrUrl): array
    {
        $token = Setting::get('delivery.fonnte_token');

        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (empty($token) || $token === 'MOCK_FONNTE_TOKEN_12345') {
            Log::info("Simulated WhatsApp send to {$phone}", [
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
                'target' => $phone,
                'message' => $message,
            ];

            if ($qrUrl) {
                $payload['url'] = $qrUrl;
            }

            Log::info("Fonnte Request", [
                'url' => 'https://api.fonnte.com/send',
                'payload' => $payload,
                'has_image' => !empty($qrUrl)
            ]);

            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', $payload);

            $body = $response->body();
            $data = $response->json();

            Log::info("Fonnte Response", [
                'status_code' => $response->status(),
                'response' => $body
            ]);

            if ($response->successful() && (($data['status'] ?? false) === true || ($data['status'] ?? '') === 'true')) {
                return [
                    'success' => true,
                    'message' => 'Sent successfully',
                    'response' => $body,
                ];
            }

            return [
                'success' => false,
                'message' => $data['reason'] ?? $data['detail'] ?? 'Fonnte API rejected request',
                'response' => $body,
            ];

        } catch (\Throwable $e) {
            Log::error("Fonnte Error", [
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
}
