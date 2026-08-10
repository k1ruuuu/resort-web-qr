<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    private const API_URL = 'https://api.fonnte.com/send';

    public function send(string $phone, string $message, ?string $qrUrl, ?string $customerName = null, ?string $qrLocalPath = null): array
    {
        $apiKey = Setting::get('delivery.fonnte_api_key');

        $originalPhone = $phone;
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        $rawPhone = $this->cleanPhoneForFonnte($phone);

        Log::info("Fonnte phone number normalization", [
            'original_masked' => $this->maskPhone($originalPhone),
            'normalized_masked' => $this->maskPhone($phone),
        ]);

        $phoneFilterMode = Setting::get('delivery.phone_filter_mode', 'global');
        if ($phoneFilterMode === 'indonesian_only') {
            if (!$this->isIndonesianNumber($phone)) {
                Log::info("Blocked non-Indonesian number", [
                    'original' => $originalPhone,
                    'normalized' => $phone,
                    'mode' => 'indonesian_only'
                ]);
                return [
                    'success' => false,
                    'message' => 'Phone number is not Indonesian. Delivery is restricted to Indonesian numbers only.',
                    'response' => json_encode(['status' => false, 'detail' => 'non_indonesian_number_blocked']),
                ];
            }
        }

        if (empty($apiKey) || $apiKey === 'MOCK_FONNTE_KEY') {
            Log::info("Simulated Fonnte send", [
                'phone' => $rawPhone,
                'message' => $message,
                'qr_url' => $qrUrl,
            ]);
            return [
                'success' => true,
                'message' => 'Simulated message sent successfully (Mock mode).',
                'response' => json_encode(['status' => true, 'detail' => 'mocked']),
            ];
        }

        try {
            if ($qrLocalPath && is_file($qrLocalPath)) {
                Log::info("Fonnte Request (file upload)", [
                    'url' => self::API_URL,
                    'target_masked' => $this->maskPhone($rawPhone),
                    'message_hash' => md5($message),
                    'file' => basename($qrLocalPath),
                ]);

                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                ])->asMultipart()->post(self::API_URL, [
                    ['name' => 'target', 'contents' => $rawPhone],
                    ['name' => 'message', 'contents' => $message],
                    ['name' => 'countryCode', 'contents' => '62'],
                    ['name' => 'file', 'contents' => fopen($qrLocalPath, 'r'), 'filename' => 'qr-voucher.png'],
                ]);
            } else {
                $payload = [
                    'target' => $rawPhone,
                    'message' => $message,
                    'countryCode' => '62',
                ];

                if ($qrUrl) {
                    $payload['url'] = $qrUrl;
                }

                Log::info("Fonnte Request", [
                    'url' => self::API_URL,
                    'target_masked' => $this->maskPhone($rawPhone),
                    'message_hash' => md5($message),
                    'has_image' => !empty($qrUrl),
                ]);

                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                ])->asForm()->post(self::API_URL, $payload);
            }

            $body = $response->body();
            $data = $response->json();

            Log::info("Fonnte Response", [
                'status_code' => $response->status(),
                'response' => $body,
            ]);

            $isSuccess = $response->successful() && ($data['status'] ?? false) === true;

            if ($isSuccess) {
                return [
                    'success' => true,
                    'message' => 'Sent successfully',
                    'response' => $body,
                ];
            }

            $errorMsg = $data['reason'] ?? $data['message'] ?? 'Fonnte API rejected request';

            return [
                'success' => false,
                'message' => $errorMsg,
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
     * L-10: mask phone numbers in logs (PII protection)
     */
    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digits) <= 4) {
            return $digits === '' ? '(empty)' : '****';
        }

        return substr($digits, 0, 3) . '****' . substr($digits, -2);
    }

    /**
     * Fonnte accepts the national number (without leading zero) when `countryCode` is provided.
     * `08123456789` → `8123456789`; `628123456789` → `8123456789`; `8123456789` → `8123456789`.
     */
    private function cleanPhoneForFonnte(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '62')) {
            return ltrim(substr($phone, 2), '0');
        }

        if (str_starts_with($phone, '0')) {
            return ltrim($phone, '0');
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
