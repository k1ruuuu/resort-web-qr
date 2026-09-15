<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\PhoneNumberHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhacenterService
{
    private const API_URL = 'https://api.whacenter.com/api/send';

    public function send(string $phone, string $message, ?string $qrUrl, ?string $customerName = null, ?string $qrLocalPath = null): array
    {
        $deviceId = Setting::get('delivery.whacenter_device_id');

        $originalPhone = $phone;
        $phone = $this->normalizePhoneForWhacenter($phone);

        Log::info("Whacenter phone number normalization", [
            'original_masked' => $this->maskPhone($originalPhone),
            'normalized_masked' => $this->maskPhone($phone),
        ]);

        $phoneFilterMode = Setting::get('delivery.phone_filter_mode', 'global');
        if ($phoneFilterMode === 'indonesian_only') {
            if (!$this->isIndonesianNumber($phone)) {
                Log::info("Blocked non-Indonesian number", [
                    'original_masked' => $this->maskPhone($originalPhone),
                    'normalized_masked' => $this->maskPhone($phone),
                    'mode' => 'indonesian_only'
                ]);
                return [
                    'success' => false,
                    'message' => 'Phone number is not Indonesian. Delivery is restricted to Indonesian numbers only.',
                    'response' => json_encode(['status' => false, 'detail' => 'non_indonesian_number_blocked']),
                ];
            }
        }

        if (empty($deviceId) || $deviceId === 'MOCK_WHACENTER_DEVICE') {
            Log::info("Simulated Whacenter send", [
                'target_masked' => $this->maskPhone($phone),
                'message_hash' => md5($message),
                'has_qr_url' => !empty($qrUrl),
            ]);
            return [
                'success' => true,
                'message' => 'Simulated message sent successfully (Mock mode).',
                'response' => json_encode(['status' => true, 'detail' => 'mocked']),
            ];
        }

        try {
            $payload = [
                'device_id' => $deviceId,
                'number' => $phone,
                'message' => $message,
            ];

            if ($qrUrl) {
                $payload['file'] = $qrUrl;
            }

            Log::info("Whacenter Request", [
                'url' => self::API_URL,
                'target_masked' => $this->maskPhone($phone),
                'message_hash' => md5($message),
                'has_file' => !empty($qrUrl),
            ]);

            $response = Http::asForm()->post(self::API_URL, $payload);

            $body = $response->body();
            $data = $response->json();

            Log::info("Whacenter Response", [
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

            $errorMsg = $data['reason'] ?? $data['message'] ?? 'Whacenter API rejected request';

            return [
                'success' => false,
                'message' => $errorMsg,
                'response' => $body,
            ];

        } catch (\Throwable $e) {
            Log::error("Whacenter Error", [
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

    private function normalizePhoneForWhacenter(string $phone): string
    {
        return PhoneNumberHelper::normalizeTo62($phone);
    }

    private function isIndonesianNumber(string $phone): bool
    {
        return PhoneNumberHelper::isIndonesian($phone);
    }
}
