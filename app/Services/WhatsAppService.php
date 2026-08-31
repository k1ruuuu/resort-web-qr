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

        // Clean phone number - remove all non-numeric except +
        $originalPhone = $phone;
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Normalize phone number format for Fonnte
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        
        Log::info("Phone number normalization", [
            'original' => $originalPhone,
            'cleaned' => $phone,
            'normalized' => $normalizedPhone,
        ]);

        // Check phone number filter mode
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

        if (empty($token) || $token === 'MOCK_FONNTE_TOKEN_12345') {
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
                'target' => $normalizedPhone,
                'message' => $message,
            ];

            if ($qrUrl) {
                $payload['url'] = $qrUrl;
            }

            Log::info("Fonnte Request", [
                'url' => 'https://api.fonnte.com/send',
                'payload' => $payload,
                'phone_original' => $originalPhone,
                'phone_normalized' => $normalizedPhone,
                'has_image' => !empty($qrUrl)
            ]);

            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', $payload);

            $body = $response->body();
            $data = $response->json();

            Log::info("Fonnte Response", [
                'status_code' => $response->status(),
                'response' => $body,
                'target_sent' => $data['target'] ?? 'unknown'
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

    /**
     * Normalize phone number for Fonnte API
     * Converts various formats to international format without + sign
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Remove leading + if present
        $phone = ltrim($phone, '+');
        
        // If starts with 0, assume Indonesian and convert to 62
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        
        // If starts with 8 and length is 10-12, assume Indonesian mobile
        if (str_starts_with($phone, '8') && strlen($phone) >= 10 && strlen($phone) <= 12) {
            $phone = '62' . $phone;
        }
        
        // If already starts with country code, keep as is
        // Otherwise, number is already in correct format or is international
        
        return $phone;
    }

    /**
     * Check if a phone number is Indonesian
     * Indonesian numbers start with:
     * - +62 (country code)
     * - 62 (without plus)
     * - 08 (local format)
     * - 8 (after removing leading zeros)
     */
    private function isIndonesianNumber(string $phone): bool
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Check various Indonesian number formats
        if (str_starts_with($phone, '+62')) {
            return true;
        }
        
        if (str_starts_with($phone, '62')) {
            return true;
        }
        
        if (str_starts_with($phone, '08')) {
            return true;
        }
        
        if (str_starts_with($phone, '8') && strlen($phone) >= 10 && strlen($phone) <= 13) {
            return true;
        }
        
        return false;
    }
}
