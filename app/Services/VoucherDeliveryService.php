<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\DeliveryLog;
use App\Models\Setting;
use App\Repositories\DeliveryLogRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherDeliveryService
{
    public function __construct(
        private readonly WhatsAppService $whatsapp,
        private readonly DeliveryLogRepository $logs,
        private readonly VoucherService $vouchers,
        private readonly QRStorageService $qrStorage,
        private readonly PublicUrlGeneratorService $urlGenerator,
    ) {}

    public function sendImmediate(Booking $booking): DeliveryLog
    {
        // Check if WhatsApp delivery is enabled
        if (Setting::get('delivery.whatsapp_enabled', '1') !== '1') {
            throw new \RuntimeException('WhatsApp delivery is currently disabled in settings.');
        }

        $booking->loadMissing(['guest', 'room.roomType']);
        
        $voucher = $booking->guestVoucher ?: $this->vouchers->generateForBooking($booking);
        
        $message = $this->compileMessage($booking);
        
        $deliveryMethod = Setting::get('delivery.delivery_method', 'qr_image');
        $filename = null;
        $qrUrl = null;
        $validationError = null;

        if ($deliveryMethod === 'qr_image') {
            try {
                $filename = $this->qrStorage->store($voucher);
                $qrUrl = $this->urlGenerator->generate($filename);
                
                if (!$this->qrStorage->exists($filename)) {
                    throw new \RuntimeException("Stored QR file does not exist on disk.");
                }
            } catch (\Throwable $e) {
                $validationError = $e->getMessage();
                Log::error("QR Generation/Validation Failed before sendImmediate", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            if ($validationError) {
                $log = $this->logs->createPending($booking, $message, null);
                $this->logs->markFailed($log->id, "Validation Error: " . $validationError);
                throw new \RuntimeException("WhatsApp QR Delivery validation failed: " . $validationError);
            }
        }

        $log = $this->logs->createPending($booking, $message, $qrUrl);

        $result = $this->whatsapp->send($booking->guest->phone ?? '', $message, $qrUrl);

        if ($result['success']) {
            $this->logs->markSent($log->id, $result['response']);
        } else {
            $this->logs->markFailed($log->id, $result['message']);
        }

        return $log->fresh();
    }

    public function schedule(Booking $booking): DeliveryLog
    {
        // Check if WhatsApp delivery is enabled
        if (Setting::get('delivery.whatsapp_enabled', '1') !== '1') {
            throw new \RuntimeException('WhatsApp delivery is currently disabled in settings.');
        }

        $booking->loadMissing(['guest', 'room.roomType']);
        
        $voucher = $booking->guestVoucher ?: $this->vouchers->generateForBooking($booking);
        
        $message = $this->compileMessage($booking);
        
        $deliveryMethod = Setting::get('delivery.delivery_method', 'qr_image');
        $filename = null;
        $qrUrl = null;
        $validationError = null;

        if ($deliveryMethod === 'qr_image') {
            try {
                $filename = $this->qrStorage->store($voucher);
                $qrUrl = $this->urlGenerator->generate($filename);
                
                if (!$this->qrStorage->exists($filename)) {
                    throw new \RuntimeException("Stored QR file does not exist on disk.");
                }
            } catch (\Throwable $e) {
                $validationError = $e->getMessage();
                Log::error("QR Scheduling Validation Failed", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            if ($validationError) {
                $log = $this->logs->createPending($booking, $message, null);
                $this->logs->markFailed($log->id, "Validation Error: " . $validationError);
                throw new \RuntimeException("WhatsApp QR Scheduling validation failed: " . $validationError);
            }
        }

        $defaultTime = Setting::get('delivery.default_time', '08:00');
        $timezone = Setting::get('delivery.timezone', 'Asia/Jakarta');

        $scheduledDateStr = $booking->check_in->toDateString();
        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', "{$scheduledDateStr} {$defaultTime}", $timezone)
            ->setTimezone('UTC');

        return $this->logs->createPending($booking, $message, $qrUrl, $scheduledAt);
    }

    public function sendManual(Booking $booking): DeliveryLog
    {
        // Check if WhatsApp delivery is enabled
        if (Setting::get('delivery.whatsapp_enabled', '1') !== '1') {
            throw new \RuntimeException('WhatsApp delivery is currently disabled in settings.');
        }

        $booking->loadMissing(['guest', 'room.roomType']);
        
        $voucher = $booking->guestVoucher ?: $this->vouchers->generateForBooking($booking);
        
        $message = $this->compileMessage($booking);
        
        $deliveryMethod = Setting::get('delivery.delivery_method', 'qr_image');
        $filename = null;
        $qrUrl = null;
        $validationError = null;

        if ($deliveryMethod === 'qr_image') {
            try {
                $filename = $this->qrStorage->store($voucher);
                $qrUrl = $this->urlGenerator->generate($filename);
                
                if (!$this->qrStorage->exists($filename)) {
                    throw new \RuntimeException("Stored QR file does not exist on disk.");
                }
            } catch (\Throwable $e) {
                $validationError = $e->getMessage();
                Log::error("QR Generation/Validation Failed before sendManual", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            if ($validationError) {
                $log = $this->logs->createPending($booking, $message, null);
                $this->logs->markFailed($log->id, "Validation Error: " . $validationError);
                throw new \RuntimeException("WhatsApp QR Delivery validation failed: " . $validationError);
            }
        }
        
        $log = $this->logs->createPending($booking, $message, $qrUrl);

        $result = $this->whatsapp->send($booking->guest->phone ?? '', $message, $qrUrl);

        if ($result['success']) {
            $this->logs->markSent($log->id, $result['response']);
        } else {
            $this->logs->markFailed($log->id, $result['message']);
        }

        return $log->fresh();
    }

    public function sendPendingLogs(): void
    {
        // Check if WhatsApp delivery is enabled
        if (Setting::get('delivery.whatsapp_enabled', '1') !== '1') {
            Log::info('WhatsApp delivery is disabled. Skipping pending logs.');
            return;
        }

        $pendingLogs = DeliveryLog::query()
            ->where('delivery_status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($pendingLogs as $log) {
            DB::transaction(function () use ($log) {
                $lockedLog = DeliveryLog::query()->lockForUpdate()->find($log->id);
                
                if ($lockedLog->delivery_status !== 'pending') {
                    return;
                }

                try {
                    if (empty($lockedLog->qr_path)) {
                        throw new \RuntimeException("QR Path URL is empty.");
                    }
                    
                    $this->urlGenerator->validateUrl($lockedLog->qr_path);
                    
                    $parsedUrl = parse_url($lockedLog->qr_path);
                    $path = $parsedUrl['path'] ?? '';
                    $filename = '';
                    if (preg_match('/storage\/(qrcodes\/qr-[a-zA-Z0-9_-]+\.png)/', $path, $matches)) {
                        $filename = $matches[1];
                    } else {
                        $filename = 'qrcodes/' . basename($path);
                    }
                    
                    if (!$this->qrStorage->exists($filename)) {
                        throw new \RuntimeException("QR image file '{$filename}' not found on disk.");
                    }
                } catch (\Throwable $e) {
                    Log::error("Validation failed for pending log ID {$lockedLog->id}: " . $e->getMessage());
                    $this->logs->markFailed($lockedLog->id, "Validation Error: " . $e->getMessage());
                    return;
                }

                $result = $this->whatsapp->send(
                    $lockedLog->phone_number,
                    $lockedLog->message_content,
                    $lockedLog->qr_path
                );

                if ($result['success']) {
                    $this->logs->markSent($lockedLog->id, $result['response']);
                } else {
                    $this->logs->markFailed($lockedLog->id, $result['message']);
                }
            });
        }
    }

    private function compileMessage(Booking $booking): string
    {
        $template = Setting::get(
            'delivery.message_template',
            "Halo {guest_name},\n\nVoucher Digital Anda telah aktif.\n\nRoom:\n{room_code}\n\nTotal Pax:\n{total_pax}\n\nSilakan tunjukkan QR berikut saat menggunakan fasilitas resort.\n\nTerima kasih."
        );

        $guestName = $booking->guest->full_name;
        $roomCode = $booking->room?->code ?? $booking->room?->number ?? 'N/A';
        $totalPax = $booking->total_pax + $booking->extra_beds;
        
        $voucher = $booking->guestVoucher;
        $voucherLink = $voucher ? route('vouchers.public', ['token' => $voucher->secure_token]) : '';

        return str_replace(
            ['{guest_name}', '{room_code}', '{total_pax}', '{voucher_link}'],
            [$guestName, $roomCode, $totalPax, $voucherLink],
            $template
        );
    }
}
