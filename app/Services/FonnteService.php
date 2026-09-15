<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\PhoneNumberHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    private const API_URL = 'https://api.fonnte.com/send';

    public function send(string $phone, string $message, ?string $qrUrl, ?string $customerName = null, ?string $qrLocalPath = null): array
    {
        $apiKey = Setting::get('delivery.fonnte_api_key');

        $originalPhone = $phone;
        $isIndo = $this->isIndonesianNumber($phone);
        $phoneClean = preg_replace('/[^0-9+]/', '', $phone);
        $rawPhone = $isIndo ? $this->cleanPhoneForFonnte($phoneClean) : preg_replace('/[^0-9]/', '', $phoneClean);
        $countryCode = $isIndo ? '62' : null;

        Log::info("Fonnte phone number normalization", [
            'original_masked' => $this->maskPhone($originalPhone),
            'normalized_masked' => $this->maskPhone($rawPhone),
            'is_indonesian' => $isIndo,
        ]);

        $phoneFilterMode = Setting::get('delivery.phone_filter_mode', 'global');
        if ($phoneFilterMode === 'indonesian_only') {
            if (!$isIndo) {
                Log::info("Blocked non-Indonesian number", [
                    'original' => $originalPhone,
                    'normalized' => $rawPhone,
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
                'country_code' => $countryCode,
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
                    'country_code' => $countryCode,
                ]);

                $multipart = [
                    ['name' => 'target', 'contents' => $rawPhone],
                    ['name' => 'message', 'contents' => $message],
                    ['name' => 'file', 'contents' => fopen($qrLocalPath, 'r'), 'filename' => 'qr-voucher.png'],
                ];
                if ($countryCode !== null) {
                    $multipart[] = ['name' => 'countryCode', 'contents' => $countryCode];
                }

                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                ])->asMultipart()->post(self::API_URL, $multipart);
            } else {
                $payload = [
                    'target' => $rawPhone,
                    'message' => $message,
                ];
                if ($countryCode !== null) {
                    $payload['countryCode'] = $countryCode;
                }

                if ($qrUrl) {
                    $payload['url'] = $qrUrl;
                }

                Log::info("Fonnte Request", [
                    'url' => self::API_URL,
                    'target_masked' => $this->maskPhone($rawPhone),
                    'message_hash' => md5($message),
                    'has_image' => !empty($qrUrl),
                    'country_code' => $countryCode,
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
        return PhoneNumberHelper::mask($phone);
    }

    private function cleanPhoneForFonnte(string $phone): string
    {
        return PhoneNumberHelper::cleanForFonnte($phone);
    }

    private function isIndonesianNumber(string $phone): bool
    {
        return PhoneNumberHelper::isIndonesian($phone);
    }
}
