<?php

namespace App\Services;

use App\Exceptions\VoucherException;
use App\Models\FacilityTemplate;
use App\Models\GuestVoucher;
use App\Models\RedemptionLog;
use App\Models\User;
use App\Models\VoucherFacilityExchange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherExchangeService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly RedisCacheService $cache,
        private readonly RedisLockService $locks,
    ) {}

    public function exchangeDinnerFacility(GuestVoucher $voucher, array $data, User $user): VoucherFacilityExchange
    {
        $allowedCodes = VoucherFacilityExchange::ALLOWED_EXCHANGE_CODES; // ['DINNER-BBQ', 'DINNER100K']

        $fromTemplateId = (int) ($data['from_facility_template_id'] ?? 0);
        $toTemplateId = (int) ($data['to_facility_template_id'] ?? 0);
        $pax = (int) ($data['pax'] ?? 0);
        $exchangeDateStr = (string) ($data['exchange_date'] ?? '');
        $notes = !empty($data['notes']) ? trim((string) $data['notes']) : null;

        if ($pax <= 0) {
            throw new VoucherException('Jumlah pax yang ditukar harus minimal 1.', 422);
        }

        if ($fromTemplateId === $toTemplateId) {
            throw new VoucherException('Fasilitas asal dan fasilitas tujuan tidak boleh sama.', 422);
        }

        $propertyId = $voucher->property_id ?? $voucher->booking?->property_id;

        $fromFacility = FacilityTemplate::query()
            ->where('id', $fromTemplateId)
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->first();

        $toFacility = FacilityTemplate::query()
            ->where('id', $toTemplateId)
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->first();

        if (!$fromFacility || !$toFacility) {
            throw new VoucherException('Fasilitas yang dipilih tidak valid untuk properti ini.', 422);
        }

        // Enforce constraint: Hanya berlaku untuk fasilitas Dinner BBQ dan Dinner 100K
        if (!in_array($fromFacility->code, $allowedCodes, true) || !in_array($toFacility->code, $allowedCodes, true)) {
            throw new VoucherException('Penukaran fasilitas hanya berlaku untuk fasilitas Dinner BBQ dan Dinner 100K.', 422);
        }

        // Validate exchange date within stay
        $timezone = $voucher->property?->timezone ?? $voucher->booking?->property?->timezone ?? 'UTC';
        $exchangeDate = Carbon::parse($exchangeDateStr, $timezone)->startOfDay();

        if ($voucher->booking) {
            $checkInStr = $voucher->booking->check_in instanceof Carbon ? $voucher->booking->check_in->toDateString() : (string)$voucher->booking->check_in;
            $checkOutStr = $voucher->booking->check_out instanceof Carbon ? $voucher->booking->check_out->toDateString() : (string)$voucher->booking->check_out;

            $checkIn = Carbon::parse($checkInStr, $timezone)->startOfDay();
            $checkOut = Carbon::parse($checkOutStr, $timezone)->startOfDay();

            if ($exchangeDate->lt($checkIn) || $exchangeDate->gt($checkOut)) {
                throw new VoucherException("Tanggal penukaran harus berada dalam rentang menginap ({$checkIn->format('Y-m-d')} s/d {$checkOut->format('Y-m-d')}).", 422);
            }
        }

        $lock = $this->locks->lockVoucherGeneration($voucher->id, 15);
        if (!$lock) {
            throw new VoucherException('Proses lain sedang berlangsung pada voucher ini. Silakan coba lagi.', 409);
        }

        try {
            return DB::transaction(function () use ($voucher, $exchangeDate, $fromFacility, $toFacility, $pax, $notes, $user) {
                // Check current remaining quota for fromFacility on exchangeDate
                $statuses = $voucher->getFacilityStatuses($exchangeDate);
                $fromStatus = $statuses->firstWhere('facility_template_id', $fromFacility->id);
                $availableRemaining = $fromStatus ? $fromStatus->quota_remaining : 0;

                if ($pax > $availableRemaining) {
                    throw new VoucherException("Jumlah pax yang ditukar ({$pax} pax) melebihi sisa kuota {$fromFacility->name} pada tanggal {$exchangeDate->format('Y-m-d')} (tersisa {$availableRemaining} pax).", 422);
                }

                $exchange = VoucherFacilityExchange::query()->create([
                    'guest_voucher_id' => $voucher->id,
                    'exchange_date' => $exchangeDate->toDateString(),
                    'from_facility_template_id' => $fromFacility->id,
                    'to_facility_template_id' => $toFacility->id,
                    'pax' => $pax,
                    'notes' => $notes,
                    'user_id' => $user->id,
                ]);

                $this->audit->log('voucher.facility_exchanged', $voucher, null, $exchange->toArray());
                $this->cache->invalidateVoucher($voucher);

                return $exchange->load(['fromFacilityTemplate', 'toFacilityTemplate', 'user']);
            });
        } finally {
            $lock->release();
        }
    }

    public function deleteExchange(VoucherFacilityExchange $exchange, User $user): void
    {
        $voucher = $exchange->guestVoucher;
        $lock = $this->locks->lockVoucherGeneration($voucher->id, 15);
        if (!$lock) {
            throw new VoucherException('Proses lain sedang berlangsung pada voucher ini. Silakan coba lagi.', 409);
        }

        try {
            DB::transaction(function () use ($exchange, $voucher, $user) {
                $exchangeDate = Carbon::parse($exchange->exchange_date);
                $targetTemplateId = $exchange->to_facility_template_id;
                
                // Redemptions used up to now for the target facility
                $used = (int) RedemptionLog::query()
                    ->where('guest_voucher_id', $voucher->id)
                    ->where('facility_template_id', $targetTemplateId)
                    ->sum('pax_used');

                // Temporarily delete to check if remaining quota would be negative
                $oldExchangeData = $exchange->toArray();
                $exchange->delete();

                $statuses = $voucher->getFacilityStatuses($exchangeDate);
                $targetStatus = $statuses->firstWhere('facility_template_id', $targetTemplateId);
                $newQuota = $targetStatus ? $targetStatus->quota_total : 0;

                if ($used > $newQuota) {
                    throw new VoucherException("Tidak dapat membatalkan penukaran karena kuota fasilitas tujuan telah digunakan sebagian/seluruhnya ({$used} pax terpakai).", 422);
                }

                $this->audit->log('voucher.facility_exchange_deleted', $voucher, $oldExchangeData, null);
                $this->cache->invalidateVoucher($voucher);
            });
        } finally {
            $lock->release();
        }
    }
}
