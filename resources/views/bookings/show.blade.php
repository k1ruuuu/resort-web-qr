@extends('layouts.app')
@section('title', 'Booking '.$booking->reference)
@section('page_title', 'Booking '.$booking->reference)
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Booking Details</h3>
                <div class="card-tools">
                    <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('bookings.destroy', $booking) }}" style="display: inline;" onsubmit="return confirm('Delete this booking?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Guest:</strong> {{ $booking->guest?->full_name ?? 'N/A' }}</p>
                <p><strong>Property:</strong> {{ $booking->property?->name ?? 'N/A' }}</p>
                <p><strong>Stay:</strong> {{ $booking->check_in?->format('Y-m-d') ?? 'N/A' }} – {{ $booking->check_out?->format('Y-m-d') ?? 'N/A' }}</p>
                <p><strong>Pax:</strong> {{ $booking->total_pax }}</p>
                <p><strong>Status:</strong> 
                    @php
                        $statusBadge = match($booking->status->value) {
                            'check_in' => 'success',
                            'expected_departure' => 'secondary',
                            'expected_arrival' => 'info',
                            'cancelled' => 'danger',
                            default => 'secondary',
                        };
                        $statusLabel = match($booking->status->value) {
                            'check_in' => 'Check In',
                            'expected_departure' => 'Expected Departure',
                            'expected_arrival' => 'Expected Arrival',
                            'cancelled' => 'Cancelled',
                            default => ucwords(str_replace('_', ' ', $booking->status->value)),
                        };
                    @endphp
                    <span class="badge bg-{{ $statusBadge }} text-white">{{ $statusLabel }}</span>
                </p>
                @if($booking->checked_in_at)
                    <p><strong>Checked In At:</strong> {{ $booking->checked_in_at->format('Y-m-d H:i:s') }}</p>
                @endif
                @if($booking->checked_out_at)
                    <p><strong>Checked Out At:</strong> {{ $booking->checked_out_at->format('Y-m-d H:i:s') }}</p>
                @endif
                @if($booking->booking_code)<p><strong>PMS Code:</strong> {{ $booking->booking_code }}</p>@endif
                @if($booking->room_label)<p><strong>Room:</strong> {{ $booking->room_label }}</p>@endif
                @if($booking->room && $booking->room->room_view)<p><strong>Room View:</strong> {{ $booking->room->room_view }}</p>@endif
                @if($booking->room && $booking->room->bed_type)<p><strong>Bed Type:</strong> {{ $booking->room->bed_type }}</p>@endif
                @if($booking->room && $booking->room->location)<p><strong>Location:</strong> Zone {{ $booking->room->location }}</p>@endif
                @if($booking->arrangement_code)<p><strong>Arrangement Code:</strong> {{ $booking->arrangement_code }}</p>@endif
                <p><strong>Quota basis:</strong> total pax ({{ $booking->total_pax }}) + {{ $booking->extra_beds }} extra bed(s) = {{ $booking->total_pax + $booking->extra_beds }} quota</p>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Facilities</div>
            <ul class="list-group list-group-flush">
                @forelse($booking->bookingFacilities as $bf)
                    <li class="list-group-item">
                        {{ $bf->facilityTemplate->name }}
                        <span class="text-muted small">— {{ $bf->start_date->format('Y-m-d') }} to {{ $bf->end_date->format('Y-m-d') }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-warning">
                        No facilities linked. Check in again to auto-attach property facilities, or recreate the booking with facilities selected.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        @can('bookings.checkin')
        @if($booking->status->value === 'expected_arrival')
        <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#checkinFacilitiesModal">
            <i class="fas fa-sign-in-alt"></i> Check In
        </button>
        @endif
        @endcan
        
        @can('bookings.checkout')
        @if($booking->status->value === 'check_in')
        <form method="POST" action="{{ route('bookings.check-out', $booking) }}" class="mb-2" onsubmit="return confirm('Check out this guest? The QR voucher will no longer be usable.');">
            @csrf
            <button class="btn btn-danger w-100">
                <i class="fas fa-sign-out-alt"></i> Check Out
            </button>
        </form>
        @endif
        @endcan
        
        @can('vouchers.generate')
        @if($booking->status->value === 'check_in' && !$booking->guestVoucher)
        <form method="POST" action="{{ route('vouchers.generate') }}">
            @csrf
            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
            <button class="btn btn-primary w-100">Generate Guest Pass</button>
        </form>
        @endif
        @endcan
        @can('vouchers.resend')
        @if($booking->status->value === 'check_in' && $booking->guestVoucher)
        <form method="POST" action="{{ route('bookings.resend', $booking) }}" class="mt-2">
            @csrf
            <button class="btn btn-warning w-100">
                <i class="fab fa-whatsapp"></i> Resend Voucher
            </button>
        </form>
        @endif
        @endcan
    </div>
</div>
@if($booking->guestVoucher)
<div class="card mt-3">
    <div class="card-header font-weight-bold {{ $booking->guestVoucher->status->value === 'active' ? 'bg-primary text-white' : 'bg-secondary text-white' }}">
        Guest Stay Pass 
        @php
            $voucherBadge = match($booking->guestVoucher->status->value) {
                'active' => 'success',
                'expired' => 'secondary',
                'cancelled' => 'danger',
                'redeemed' => 'info',
                default => 'secondary',
            };
        @endphp
        <span class="badge bg-{{ $voucherBadge }} text-white float-end">{{ ucfirst($booking->guestVoucher->status->value) }}</span>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <x-qr-code :url="route('vouchers.qr', $booking->guestVoucher)" :size="150" class="rounded border bg-white p-1" />
            </div>
            <div class="col-md-8">
                <p class="mb-1"><strong>QR Code Text:</strong> <code class="text-dark">{{ $booking->guestVoucher->qr_code }}</code></p>
                <p class="mb-1"><strong>Secure Token:</strong> <code class="text-muted">{{ substr($booking->guestVoucher->secure_token, 0, 12) }}...</code></p>
                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-{{ $voucherBadge }} text-white">{{ ucfirst($booking->guestVoucher->status->value) }}</span></p>
                <p class="mb-1"><strong>Generated At:</strong> {{ $booking->guestVoucher->generated_at?->format('Y-m-d H:i:s') }}</p>
                @if($booking->guestVoucher->status->value !== 'active')
                    <div class="alert alert-warning mt-2 mb-2">
                        <i class="fas fa-exclamation-triangle"></i> This voucher is no longer active and cannot be used for redemption.
                    </div>
                @endif
                <p class="mb-0 mt-2">
                    <a href="{{ route('vouchers.show', $booking->guestVoucher) }}" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-eye"></i> View Card Details
                    </a>
                    <a href="{{ route('vouchers.public', $booking->guestVoucher->secure_token) }}" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-external-link-alt"></i> Open Public Link
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="checkinFacilitiesModal" tabindex="-1" aria-labelledby="checkinFacilitiesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('bookings.check-in', $booking) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="checkinFacilitiesModalLabel">Check In Guest</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="stepPhone">
                        <p class="text-muted small">Enter the guest's phone number for WhatsApp delivery.</p>
                        <div class="mb-3">
                            <label class="form-label" for="guestPhone">Phone Number</label>
                            <input type="text" class="form-control" id="guestPhone" name="phone" value="{{ $booking->guest->phone ?? '' }}" placeholder="e.g. 6281234567890">
                            <div class="form-text">Include country code (e.g. 62 for Indonesia).</div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-primary" id="btnToFacilities">Next</button>
                        </div>
                    </div>
                    <div id="stepFacilities" style="display:none;">
                        <p class="text-muted small">Pilih fasilitas yang disertakan untuk QR voucher tamu ini. Biarkan kosong untuk menggunakan fasilitas standar.</p>
                        
                        @php
                            $standardFacilities = $facilityTemplates->reject(fn($f) => $f->isDinner());
                            $dinnerFacilities = $facilityTemplates->filter(fn($f) => $f->isDinner());
                            $activeDinnerId = $booking->bookingFacilities->first(fn($bf) => $bf->facilityTemplate?->isDinner())?->facility_template_id;
                            if (!$activeDinnerId && $dinnerFacilities->isNotEmpty()) {
                                $defaultDinner = $dinnerFacilities->firstWhere('code', \App\Models\FacilityTemplate::DEFAULT_DINNER_CODE) ?? $dinnerFacilities->first();
                                $defaultDinnerId = $defaultDinner?->id;
                            } else {
                                $defaultDinnerId = $activeDinnerId;
                            }
                        @endphp

                        <div class="mb-3">
                            @if($facilityTemplates->isNotEmpty())
                                <div class="form-check mb-3 p-2 bg-light border rounded">
                                    <input class="form-check-input" type="checkbox" id="selectAllFacilities">
                                    <label class="form-check-label fw-bold text-primary" for="selectAllFacilities">
                                        <i class="fas fa-check-double me-2"></i>Pilih Semua Fasilitas Standar + Dinner
                                    </label>
                                </div>
                            @endif

                            @if($standardFacilities->isNotEmpty())
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Fasilitas Standar</label>
                                    <div class="bg-light border rounded p-3">
                                        @foreach($standardFacilities as $facilityTemplate)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input facility-checkbox standard-facility-checkbox" type="checkbox" name="facility_template_ids[]" value="{{ $facilityTemplate->id }}" id="facility_{{ $facilityTemplate->id }}" @checked($booking->bookingFacilities->isEmpty() || $booking->bookingFacilities->contains('facility_template_id', $facilityTemplate->id))>
                                                <label class="form-check-label" for="facility_{{ $facilityTemplate->id }}">
                                                    {{ $facilityTemplate->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($dinnerFacilities->isNotEmpty())
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-semibold small text-muted text-uppercase mb-0">Paket Dinner (Pilih Salah Satu)</label>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-info-circle me-1"></i>Pilih Maks. 1</span>
                                    </div>
                                    <div class="alert alert-warning py-1 px-2 small mb-2 text-muted">
                                        <i class="fas fa-exchange-alt me-1 text-primary"></i> Tamu hanya dapat memilih salah satu paket Dinner saat Check-In. Kuota dinner dapat ditukar antar tanggal menginap setelah check-in.
                                    </div>
                                    <div class="bg-light border border-warning rounded p-3">
                                        @foreach($dinnerFacilities as $facilityTemplate)
                                            @php
                                                $isDefaultSelected = ($booking->bookingFacilities->isNotEmpty() && $booking->bookingFacilities->contains('facility_template_id', $facilityTemplate->id))
                                                    || ($booking->bookingFacilities->isEmpty() && $facilityTemplate->id === $defaultDinnerId);
                                            @endphp
                                            <div class="form-check mb-2">
                                                <input class="form-check-input facility-checkbox dinner-facility-checkbox" 
                                                       type="checkbox" 
                                                       name="facility_template_ids[]" 
                                                       value="{{ $facilityTemplate->id }}" 
                                                       id="facility_{{ $facilityTemplate->id }}" 
                                                       data-code="{{ $facilityTemplate->code }}"
                                                       @checked($isDefaultSelected)>
                                                <label class="form-check-label fw-semibold" for="facility_{{ $facilityTemplate->id }}">
                                                    {{ $facilityTemplate->name }}
                                                    @if($facilityTemplate->code === 'DINNER-BBQ')
                                                        <span class="badge bg-primary text-white ms-1">Default BBQ</span>
                                                    @elseif($facilityTemplate->code === 'DINNER100K')
                                                        <span class="badge bg-info text-white ms-1">Voucher Resto 100K</span>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($facilityTemplates->isEmpty())
                                <p class="text-muted small mb-0">No facility templates available for this property.</p>
                            @endif

                            <div class="form-text">Pastikan pilihan fasilitas sudah sesuai dengan paket menginap tamu.</div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" id="btnBackToPhone">Back</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-check-circle me-1"></i>Confirm Check In</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce }}">
    (function () {
        const stepPhone = document.getElementById('stepPhone');
        const stepFacilities = document.getElementById('stepFacilities');
        const btnToFacilities = document.getElementById('btnToFacilities');
        const btnBackToPhone = document.getElementById('btnBackToPhone');
        const guestPhone = document.getElementById('guestPhone');

        btnToFacilities.addEventListener('click', function () {
            const phone = guestPhone.value.trim();
            if (!phone) {
                guestPhone.classList.add('is-invalid');
                if (!document.querySelector('#guestPhone + .invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'Phone number is required.';
                    guestPhone.parentNode.appendChild(feedback);
                }
                return;
            }
            guestPhone.classList.remove('is-invalid');
            stepPhone.style.display = 'none';
            stepFacilities.style.display = 'block';
        });

        btnBackToPhone.addEventListener('click', function () {
            stepFacilities.style.display = 'none';
            stepPhone.style.display = 'block';
        });

        guestPhone.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        });

        const selectAllFacilitiesCheckbox = document.getElementById('selectAllFacilities');
        const standardCheckboxes = document.querySelectorAll('#checkinFacilitiesModal .standard-facility-checkbox');
        const dinnerCheckboxes = document.querySelectorAll('#checkinFacilitiesModal .dinner-facility-checkbox');

        // Mutually exclusive dinner checkboxes
        dinnerCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (this.checked) {
                    dinnerCheckboxes.forEach(otherCb => {
                        if (otherCb !== this) {
                            otherCb.checked = false;
                        }
                    });
                }
                updateSelectAllState();
            });
        });

        // Standard checkboxes change
        standardCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                updateSelectAllState();
            });
        });

        function updateSelectAllState() {
            if (!selectAllFacilitiesCheckbox) return;
            const allStandardChecked = Array.from(standardCheckboxes).every(cb => cb.checked);
            const hasOneDinnerChecked = Array.from(dinnerCheckboxes).some(cb => cb.checked);
            selectAllFacilitiesCheckbox.checked = allStandardChecked && (dinnerCheckboxes.length === 0 || hasOneDinnerChecked);
        }

        if (selectAllFacilitiesCheckbox) {
            selectAllFacilitiesCheckbox.addEventListener('change', function () {
                const check = this.checked;
                standardCheckboxes.forEach(cb => {
                    cb.checked = check;
                });
                
                if (check) {
                    // Check default dinner (DINNER-BBQ) or the first dinner option, and uncheck others
                    let dinnerChecked = false;
                    dinnerCheckboxes.forEach(cb => {
                        if (!dinnerChecked && (cb.dataset.code === 'DINNER-BBQ' || cb === dinnerCheckboxes[0])) {
                            cb.checked = true;
                            dinnerChecked = true;
                        } else {
                            cb.checked = false;
                        }
                    });
                } else {
                    dinnerCheckboxes.forEach(cb => {
                        cb.checked = false;
                    });
                }
            });

            updateSelectAllState();
        }
    })();
</script>
@endpush
@endsection
