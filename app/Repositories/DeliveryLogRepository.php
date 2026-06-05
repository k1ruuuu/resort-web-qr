<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\DeliveryLog;
use Carbon\Carbon;

class DeliveryLogRepository
{
    public function createPending(
        Booking $booking,
        string $message,
        ?string $qrPath,
        ?Carbon $scheduledAt = null
    ): DeliveryLog {
        return DeliveryLog::query()->create([
            'booking_id' => $booking->id,
            'guest_id' => $booking->guest_id,
            'phone_number' => $booking->guest->phone ?? '',
            'message_content' => $message,
            'qr_path' => $qrPath,
            'delivery_status' => 'pending',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function markSent(int $logId, ?string $response = null): void
    {
        DeliveryLog::query()->where('id', $logId)->update([
            'delivery_status' => 'sent',
            'sent_at' => now(),
            'provider_response' => $response,
        ]);
    }

    public function markFailed(int $logId, string $error): void
    {
        DeliveryLog::query()->where('id', $logId)->update([
            'delivery_status' => 'failed',
            'sent_at' => now(),
            'provider_response' => $error,
        ]);
    }
}
