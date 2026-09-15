@extends('layouts.app')
@section('title', 'Digital Guest Voucher')
@section('page_title', 'Digital Guest Voucher')
@section('content')
<div class="row g-4">
    <!-- Left Column: Voucher Card & Info -->
    <div class="col-lg-5">
        <div class="card card-primary card-outline shadow-sm h-100">
            <div class="card-body box-profile text-center">
                <div class="text-center mb-3">
                    <i class="fas fa-id-card fa-3x text-primary"></i>
                </div>
                <h3 class="profile-username text-center font-weight-bold mb-1">{{ $voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest') }}</h3>
                <p class="text-muted text-center mb-4">Digital Guest Card</p>

                <div class="p-3 bg-light rounded border mb-4">
                    <x-qr-code :url="$qrImageUrl" :size="200" class="rounded shadow-sm" />
                    <p class="mt-3 mb-0 small text-muted text-monospace">
                        QR Code: <code>{{ substr($voucher->qr_code, 0, 12) }}{{ strlen($voucher->qr_code) > 12 ? '...' : '' }}</code>
                    </p>
                </div>

                <ul class="list-group list-group-unbordered mb-4 text-start">
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Room</b> 
                        <span>{{ $voucher->booking?->room_label ?? $voucher->booking?->room?->label ?? 'Temporary' }} ({{ $voucher->booking?->room?->code ?? 'TEMP' }})</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Stay Dates</b> 
                        <span>{{ $voucher->booking ? $voucher->booking->check_in->format('d M Y') . ' – ' . $voucher->booking->check_out->format('d M Y') : ($voucher->expires_at_local ? $voucher->expires_at_local->format('d M Y H:i') : 'N/A') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Total Pax</b> 
                        <span>
                            @if($voucher->booking)
                                {{ $voucher->booking->total_pax }} (+ {{ $voucher->booking->extra_beds }} Extra Bed){{ $voucher->addition ? ' (+' . $voucher->addition . ' Addition)' : '' }} = <strong>{{ $voucher->booking->total_pax + $voucher->booking->extra_beds + ($voucher->addition ?? 0) }} Quota</strong>
                            @else
                                {{ ($voucher->pax_limit ?? 1) + ($voucher->addition ?? 0) }}
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Booking Ref</b> 
                        <span>{{ $voucher->booking?->booking_code ?? $voucher->booking?->reference ?? 'Temporary' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Status</b> 
                        <span class="badge bg-{{ $voucher->status->value === 'active' ? 'success' : 'secondary' }} d-flex align-items-center px-2">{{ ucfirst($voucher->status->value) }}</span>
                    </li>
                </ul>

                <div class="d-grid gap-2">
                    <a href="{{ route('vouchers.public', $voucher->secure_token) }}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-1"></i> View Public Guest Card
                    </a>
                    
                    @can('vouchers.edit')
                    <a href="{{ route('vouchers.edit', $voucher) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i> Edit Voucher Facilities
                    </a>
                    @endcan

                    @can('vouchers.resend')
                    @if($voucher->booking_id)
                    <form method="POST" action="{{ route('bookings.resend', $voucher->booking_id) }}" class="d-grid">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fab fa-whatsapp me-1"></i> Resend Voucher via WhatsApp
                        </button>
                    </form>
                    @elseif($voucher->phone)
                    <form method="POST" action="{{ route('vouchers.resend', $voucher) }}" class="d-grid">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fab fa-whatsapp me-1"></i> Send Voucher via WhatsApp
                        </button>
                    </form>
                    @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Facilities Status & Dinner Exchange -->
    <div class="col-lg-7">
        <!-- Today's Facility Statuses -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-concierge-bell me-2 text-primary"></i>Status Fasilitas Hari Ini ({{ \Carbon\Carbon::today()->format('d M Y') }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fasilitas</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Terpakai</th>
                                <th class="text-center">Sisa</th>
                                <th class="text-center">Total Kuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayStatuses as $fac)
                            <tr>
                                <td>
                                    <strong>{{ $fac->name }}</strong>
                                    <br><small class="text-muted text-monospace">{{ $fac->code }}</small>
                                </td>
                                <td class="text-center">
                                    @if($fac->status === 'available')
                                        <span class="badge bg-success">Tersedia</span>
                                    @elseif($fac->status === 'used')
                                        <span class="badge bg-warning text-dark">Habis Terpakai</span>
                                    @else
                                        <span class="badge bg-secondary">Tidak Berlaku</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold text-danger">{{ $fac->quota_used }}</td>
                                <td class="text-center fw-bold text-success">{{ $fac->quota_remaining }}</td>
                                <td class="text-center text-muted">{{ $fac->quota_total }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Tidak ada fasilitas aktif untuk hari ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dinner Facility Exchanges (Dinner BBQ <-> Dinner 100K) -->
        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold"><i class="fas fa-exchange-alt me-2 text-primary"></i>Penukaran Fasilitas Dinner</h6>
                    <small class="text-muted">Khusus penukaran antara <strong>Dinner BBQ</strong> dan <strong>Dinner 100K</strong></small>
                </div>
                @can('vouchers.edit')
                @if($exchangeableFacilities->count() >= 2)
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#exchangeModal">
                    <i class="fas fa-plus me-1"></i> Tukar Fasilitas Dinner
                </button>
                @endif
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Dari Fasilitas</th>
                                <th>Ke Fasilitas</th>
                                <th class="text-center">Pax</th>
                                <th>Catatan</th>
                                <th>Staf</th>
                                @can('vouchers.edit')
                                <th class="text-end">Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($facilityExchanges as $exchange)
                            <tr>
                                <td>
                                    <strong>{{ $exchange->exchange_date->format('d M Y') }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $exchange->fromFacilityTemplate?->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">{{ $exchange->toFacilityTemplate?->name ?? 'N/A' }}</span>
                                </td>
                                <td class="text-center fw-bold text-primary">
                                    {{ $exchange->pax }} pax
                                </td>
                                <td>
                                    <small class="text-muted">{{ $exchange->notes ?: '-' }}</small>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $exchange->user?->name ?? 'System' }}</small>
                                </td>
                                @can('vouchers.edit')
                                <td class="text-end">
                                    <form method="POST" action="{{ route('vouchers.exchanges.destroy', [$voucher, $exchange]) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan penukaran ini? Kuota akan dikembalikan ke fasilitas asal.');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan Penukaran">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                                @endcan
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-exchange-alt fa-2x text-muted mb-2 d-block"></i>
                                    Belum ada penukaran fasilitas Dinner yang dicatat untuk voucher ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tukar Fasilitas Dinner -->
@can('vouchers.edit')
@if($exchangeableFacilities->count() >= 2)
<div class="modal fade" id="exchangeModal" tabindex="-1" aria-labelledby="exchangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('vouchers.exchanges.store', $voucher) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exchangeModalLabel"><i class="fas fa-exchange-alt text-primary me-2"></i>Tukar Fasilitas Dinner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i> Penukaran kuota dinner berlaku untuk tanggal yang ditentukan (hanya antara <strong>Dinner BBQ</strong> dan <strong>Dinner 100K</strong>).
                    </div>

                    @php
                        $bbqTemplate = $exchangeableFacilities->firstWhere('code', 'DINNER-BBQ');
                        $d100kTemplate = $exchangeableFacilities->firstWhere('code', 'DINNER100K');
                        $defaultFromId = $bbqTemplate ? $bbqTemplate->id : $exchangeableFacilities->first()->id;
                        $defaultToId = $d100kTemplate ? $d100kTemplate->id : $exchangeableFacilities->last()->id;
                        $defaultDate = \Carbon\Carbon::today()->toDateString();
                        if ($voucher->booking) {
                            $ci = $voucher->booking->check_in->toDateString();
                            $co = $voucher->booking->check_out->toDateString();
                            if ($defaultDate < $ci) $defaultDate = $ci;
                            if ($defaultDate > $co) $defaultDate = $co;
                        }
                    @endphp

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Tanggal Penukaran <span class="text-danger">*</span></label>
                        <input type="date" name="exchange_date" class="form-control"
                               value="{{ old('exchange_date', $defaultDate) }}"
                               @if($voucher->booking)
                               min="{{ $voucher->booking->check_in->toDateString() }}"
                               max="{{ $voucher->booking->check_out->toDateString() }}"
                               @endif
                               required>
                        <div class="form-text small">Pilih tanggal menginap di mana penukaran kuota ini berlaku.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Dari Fasilitas <span class="text-danger">*</span></label>
                            <select name="from_facility_template_id" id="fromFacilitySelect" class="form-select" required>
                                @foreach($exchangeableFacilities as $facility)
                                    <option value="{{ $facility->id }}" {{ old('from_facility_template_id', $defaultFromId) == $facility->id ? 'selected' : '' }}>
                                        {{ $facility->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Ke Fasilitas <span class="text-danger">*</span></label>
                            <select name="to_facility_template_id" id="toFacilitySelect" class="form-select" required>
                                @foreach($exchangeableFacilities as $facility)
                                    <option value="{{ $facility->id }}" {{ old('to_facility_template_id', $defaultToId) == $facility->id ? 'selected' : '' }}>
                                        {{ $facility->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Jumlah Pax Ditukar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="pax" class="form-control" min="1" max="50" value="{{ old('pax', 1) }}" required>
                            <span class="input-group-text">pax</span>
                        </div>
                        <div class="form-text small">Jumlah kuota yang ingin dialihkan (misal: 2 pax).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Catatan / Alasan (Opsional)</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Contoh: Tamu meminta tukar 2 pax BBQ ke Dinner 100K">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Simpan Penukaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endcan
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function () {
    const fromSelect = document.getElementById('fromFacilitySelect');
    const toSelect = document.getElementById('toFacilitySelect');

    if (fromSelect && toSelect) {
        fromSelect.addEventListener('change', function () {
            if (this.value === toSelect.value) {
                // Auto switch to other option
                for (let opt of toSelect.options) {
                    if (opt.value !== this.value) {
                        toSelect.value = opt.value;
                        break;
                    }
                }
            }
        });

        toSelect.addEventListener('change', function () {
            if (this.value === fromSelect.value) {
                // Auto switch to other option
                for (let opt of fromSelect.options) {
                    if (opt.value !== this.value) {
                        fromSelect.value = opt.value;
                        break;
                    }
                }
            }
        });
    }
});
</script>
@endpush
