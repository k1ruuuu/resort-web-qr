<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\DeliveryLog;
use App\Models\GuestVoucher;
use App\Models\Setting;
use App\Repositories\DeliveryLogRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VoucherDeliveryService
{
    public function __construct(
        private readonly FonnteService $fonnte,
        private readonly WhacenterService $whacenter,
        private readonly DeliveryLogRepository $logs,
        private readonly VoucherService $vouchers,
        private readonly QRStorageService $qrStorage,
        private readonly PublicUrlGeneratorService $urlGenerator,
    ) {}

    private function sender(): FonnteService|WhacenterService
    {
        $provider = Setting::get('delivery.whatsapp_provider', 'Fonnte');
        return strtolower($provider) === 'whacenter' ? $this->whacenter : $this->fonnte;
    }

    private function prepareQrImage(GuestVoucher $voucher): array
    {
        $filename = $this->qrStorage->store($voucher);

        if (!$this->qrStorage->exists($filename)) {
            throw new \RuntimeException("Stored QR file does not exist on disk.");
        }

        $qrUrl = null;
        try {
            $qrUrl = $this->urlGenerator->generate($filename);
        } catch (\Throwable $e) {
            Log::warning("QR public URL generation skipped (file will be uploaded directly): " . $e->getMessage());
        }

        return [
            'filename' => $filename,
            'qr_url' => $qrUrl,
            'qr_local_path' => Storage::disk('public')->path($filename),
        ];
    }

    public function sendImmediate(Booking $booking): DeliveryLog
    {
        if (Setting::get('delivery.whatsapp_enabled', '1') !== '1') {
            throw new \RuntimeException('WhatsApp delivery is currently disabled in settings.');
        }

        $booking->loadMissing(['guest', 'room.roomType']);
        
        $voucher = $booking->guestVoucher ?: $this->vouchers->generateForBooking($booking);
        
        $message = $this->compileMessage($booking);
        
        $deliveryMethod = Setting::get('delivery.delivery_method', 'qr_image');
        $filename = null;
        $qrUrl = null;
        $qrLocalPath = null;
        $validationError = null;

        if ($deliveryMethod === 'qr_image') {
            try {
                $qr = $this->prepareQrImage($voucher);
                $filename = $qr['filename'];
                $qrUrl = $qr['qr_url'];
                $qrLocalPath = $qr['qr_local_path'];
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

        $result = $this->sender()->send(
            $booking->guest?->phone ?? '',
            $message,
            $qrUrl,
            $booking->guest?->full_name ?? null,
            $qrLocalPath
        );

        if ($result['success']) {
            $this->logs->markSent($log->id, $result['response']);
        } else {
            $this->logs->markFailed($log->id, $result['message']);
        }

        return $log->fresh();
    }

    public function schedule(Booking $booking): DeliveryLog
    {
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
                $qr = $this->prepareQrImage($voucher);
                $filename = $qr['filename'];
                $qrUrl = $qr['qr_url'];
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
        if (Setting::get('delivery.whatsapp_enabled', '1') !== '1') {
            throw new \RuntimeException('WhatsApp delivery is currently disabled in settings.');
        }

        $booking->loadMissing(['guest', 'room.roomType']);
        
        $voucher = $booking->guestVoucher ?: $this->vouchers->generateForBooking($booking);
        
        $message = $this->compileMessage($booking);
        
        $deliveryMethod = Setting::get('delivery.delivery_method', 'qr_image');
        $filename = null;
        $qrUrl = null;
        $qrLocalPath = null;
        $validationError = null;

        if ($deliveryMethod === 'qr_image') {
            try {
                $qr = $this->prepareQrImage($voucher);
                $filename = $qr['filename'];
                $qrUrl = $qr['qr_url'];
                $qrLocalPath = $qr['qr_local_path'];
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

        $result = $this->sender()->send(
            $booking->guest?->phone ?? '',
            $message,
            $qrUrl,
            $booking->guest?->full_name ?? null,
            $qrLocalPath
        );

        if ($result['success']) {
            $this->logs->markSent($log->id, $result['response']);
        } else {
            $this->logs->markFailed($log->id, $result['message']);
        }

        return $log->fresh();
    }

    public function sendVoucherImmediate(GuestVoucher $voucher): DeliveryLog
    {
        if (Setting::get('delivery.whatsapp_enabled', '1') !== '1') {
            throw new \RuntimeException('WhatsApp delivery is currently disabled in settings.');
        }

        if (!$voucher->phone) {
            throw new \RuntimeException('Temporary voucher has no phone number to send to.');
        }

        $message = $this->compileVoucherMessage($voucher);

        $deliveryMethod = Setting::get('delivery.delivery_method', 'qr_image');
        $qrUrl = null;
        $qrLocalPath = null;
        $validationError = null;

        if ($deliveryMethod === 'qr_image') {
            try {
                $qr = $this->prepareQrImage($voucher);
                $qrUrl = $qr['qr_url'];
                $qrLocalPath = $qr['qr_local_path'];
            } catch (\Throwable $e) {
                $validationError = $e->getMessage();
                Log::error("QR Generation/Validation Failed before sendVoucherImmediate", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            if ($validationError) {
                $log = $this->logs->createPendingForVoucher($voucher, $message, null);
                $this->logs->markFailed($log->id, "Validation Error: " . $validationError);
                throw new \RuntimeException("WhatsApp QR Delivery validation failed: " . $validationError);
            }
        }

        $log = $this->logs->createPendingForVoucher($voucher, $message, $qrUrl);

        $result = $this->sender()->send(
            $voucher->phone,
            $message,
            $qrUrl,
            $voucher->guest_name ?? null,
            $qrLocalPath
        );

        if ($result['success']) {
            $this->logs->markSent($log->id, $result['response']);
        } else {
            $this->logs->markFailed($log->id, $result['message']);
        }

        return $log->fresh();
    }

    private function compileVoucherMessage(GuestVoucher $voucher): string
    {
        $template = Setting::get(
            'delivery.message_template',
            "Halo {guest_name},\n\nVoucher Digital Anda telah aktif.\n\nRoom:\n{room_name}\n\nTotal Pax:\n{total_pax}\n\nSilakan tunjukkan QR berikut saat menggunakan fasilitas resort.\n\nTerima kasih."
        );

        $guestName = $voucher->guest_name ?? 'Guest';
        $roomName = $voucher->booking?->room_label
            ?? $voucher->booking?->room?->label
            ?? $voucher->booking?->room?->number
            ?? 'TEMP';
        $totalPax = ($voucher->pax_limit ?? 1) + ($voucher->addition ?? 0);
        $voucherLink = route('vouchers.public', ['token' => $voucher->secure_token]);

        $facilities = $voucher->getFacilityStatuses(Carbon::today($voucher->property?->timezone ?? $voucher->booking?->property?->timezone ?? 'UTC'))
            ->filter()
            ->map(fn($f) => "- {$f->name} ({$f->quota_remaining} pax)")
            ->implode("\n");

        return str_replace(
            ['{guest_name}', '{room_code}', '{room_name}', '{total_pax}', '{voucher_link}', '{facility_access}'],
            [$guestName, $voucher->booking?->room?->code ?? 'TEMP', $roomName, $totalPax, $voucherLink, $facilities],
            $template
        );
    }

    public function sendPendingLogs(): void
    {
        if (Setting::get('delivery.whatsapp_enabled', '1') !== '1') {
            Log::info('WhatsApp delivery is disabled. Skipping pending logs.');
            return;
        }

        $pendingLogs = DeliveryLog::query()
            ->with('booking.guest')
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
                    $qrLocalPath = null;

                    if (!empty($lockedLog->qr_path)) {
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

                        $qrLocalPath = $this->qrStorage->getAbsolutePath($filename);
                    }
                } catch (\Throwable $e) {
                    Log::error("Validation failed for pending log ID {$lockedLog->id}: " . $e->getMessage());
                    $this->logs->markFailed($lockedLog->id, "Validation Error: " . $e->getMessage());
                    return;
                }

                $guestName = $lockedLog->booking?->guest?->full_name;
                $result = $this->sender()->send(
                    $lockedLog->phone_number,
                    $lockedLog->message_content,
                    $lockedLog->qr_path,
                    $guestName,
                    $qrLocalPath
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
            "Halo {guest_name},\n\nVoucher Digital Anda telah aktif.\n\nRoom:\n{room_name}\n\nTotal Pax:\n{total_pax}\n\nSilakan tunjukkan QR berikut saat menggunakan fasilitas resort.\n\nTerima kasih."
        );

        $guestName = $booking->guest?->full_name ?? 'Guest';
        $roomCode = $booking->room?->code ?? $booking->room?->number ?? 'N/A';
        $roomName = $booking->room_label ?? $booking->room?->label ?? $roomCode;
        $totalPax = $booking->total_pax + $booking->extra_beds;

        $voucher = $booking->guestVoucher;
        $voucherLink = $voucher ? route('vouchers.public', ['token' => $voucher->secure_token]) : '';

        $facilities = '';
        if ($voucher) {
            $facilities = $voucher->getFacilityStatuses(Carbon::today($voucher->property?->timezone ?? $booking->property?->timezone ?? 'UTC'))
                ->filter()
                ->map(fn($f) => "- {$f->name} ({$f->quota_remaining} pax)")
                ->implode("\n");
        }

        return str_replace(
            ['{guest_name}', '{room_code}', '{room_name}', '{total_pax}', '{voucher_link}', '{facility_access}'],
            [$guestName, $roomCode, $roomName, $totalPax, $voucherLink, $facilities],
            $template
        );
    }
}
