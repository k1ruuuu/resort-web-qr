@extends('layouts.app')
@section('title', 'Bookings')
@section('page_title', 'Bookings')
@section('content')
<div class="mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    @can('bookings.create')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-responsive">
            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">New</span> Booking
        </a>
        <a href="{{ route('bookings.import') }}" class="btn btn-outline-primary btn-responsive">
            <i class="fas fa-upload"></i> Import
        </a>
    </div>
    @else
    <div></div>
    @endcan
    
    <button class="btn btn-outline-secondary btn-responsive filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
        <i class="fas fa-filter"></i> Filters
    </button>
</div>

<!-- Search & Filter Form -->
<div class="collapse filter-collapse mb-3 {{ request()->hasAny(['search', 'status', 'property_id', 'date_from', 'date_to']) ? 'show' : '' }}" id="filterCollapse">
    <div class="card card-responsive">
        <div class="card-body">
            <form method="GET" action="{{ route('bookings.index') }}">
                <div class="row g-3 form-row-responsive">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Name, email, room..."
                               value="{{ request('search') }}">
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="expected_arrival" {{ request('status') === 'expected_arrival' ? 'selected' : '' }}>Expected Arrival</option>
                            <option value="check_in" {{ request('status') === 'check_in' ? 'selected' : '' }}>Check In</option>
                            <option value="expected_departure" {{ request('status') === 'expected_departure' ? 'selected' : '' }}>Expected Departure</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">Property</label>
                        <select name="property_id" class="form-select">
                            <option value="">All</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                                    {{ $property->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" 
                               name="date_from" 
                               class="form-control" 
                               value="{{ request('date_from') }}">
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" 
                               name="date_to" 
                               class="form-control" 
                               value="{{ request('date_to') }}">
                    </div>
                    
                    <div class="col-12 col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i><span class="d-none d-lg-inline ms-1">Go</span>
                        </button>
                    </div>
                </div>
                
                @if(request()->hasAny(['search', 'status', 'property_id', 'date_from', 'date_to']))
                <div class="mt-2">
                    <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>

<!-- Bulk Action Bar -->
<div id="bulkActionBar" class="card shadow-sm border-primary mb-3 bg-white" style="display: none;">
    <div class="card-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary fs-6 px-2 py-1" id="bulkSelectedBadge">0</span>
            <span class="fw-semibold text-dark">Kamar Rombongan Dipilih</span>
            <span class="text-muted small d-none d-md-inline" id="bulkSelectedPreview"></span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll">
                <i class="fas fa-times me-1"></i> Batal Pilih
            </button>
            @can('bookings.checkin')
            <button type="button" class="btn btn-sm btn-success" id="btnOpenBulkModal" data-bs-toggle="modal" data-bs-target="#bulkCheckinModal">
                <i class="fas fa-users me-1"></i> Check-In Rombongan
            </button>
            @endcan
        </div>
    </div>
</div>

<div class="card card-responsive">
    <div class="card-body p-0">
        <div class="table-responsive overflow-auto-mobile">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width: 38px;">
                            <input type="checkbox" id="selectAllBookings" class="form-check-input" title="Pilih Semua yang Belum Check-In">
                        </th>
                        <th>Reference</th>
                        <th>Guest</th>
                        <th class="d-none d-md-table-cell">Property</th>
                        <th>Room</th>
                        <th class="d-none d-lg-table-cell">Stay</th>
                        <th class="d-none d-sm-table-cell">Pax</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($bookings as $booking)
                    <tr>
                        <td>
                            @if($booking->status === \App\Enums\BookingStatus::ExpectedArrival)
                                <input type="checkbox" 
                                       class="form-check-input booking-select-checkbox" 
                                       value="{{ $booking->id }}" 
                                       data-id="{{ $booking->id }}"
                                       data-reference="{{ $booking->reference }}"
                                       data-room="{{ $booking->room_label ?? $booking->room?->number ?? $booking->reference }}"
                                       data-guest="{{ $booking->guest?->full_name ?? 'N/A' }}"
                                       data-phone="{{ $booking->guest?->phone ?? '' }}">
                            @else
                                <span class="text-muted small" title="{{ $booking->status->value === 'check_in' ? 'Checked In' : $booking->status->value }}">
                                    <i class="fas {{ $booking->status->value === 'check_in' ? 'fa-check-circle text-success' : 'fa-minus text-muted' }}"></i>
                                </span>
                            @endif
                        </td>
                        <td><strong class="text-truncate d-inline-block" style="max-width: 100px;">{{ $booking->reference }}</strong></td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $booking->guest?->full_name ?? 'N/A' }}">
                                {{ $booking->guest?->full_name ?? 'N/A' }}
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="text-truncate" style="max-width: 120px;" title="{{ $booking->property?->name ?? 'N/A' }}">
                                {{ $booking->property?->name ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $booking->room_label ?? $booking->room?->number ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <small>{{ $booking->check_in?->format('M d') ?? 'N/A' }} – {{ $booking->check_out?->format('M d') ?? 'N/A' }}</small>
                        </td>
                        <td class="d-none d-sm-table-cell">{{ $booking->total_pax }}</td>
                        <td>
                            @php
                                $badgeMap = ['check_in' => 'success', 'expected_arrival' => 'info', 'expected_departure' => 'secondary', 'cancelled' => 'danger'];
                                $labelMap = ['check_in' => 'Check In', 'expected_arrival' => 'Expected Arrival', 'expected_departure' => 'Expected Departure', 'cancelled' => 'Cancelled'];
                            @endphp
                            <span class="badge bg-{{ $badgeMap[$booking->status->value] ?? 'secondary' }} text-white">
                                <span class="d-none d-sm-inline">{{ $labelMap[$booking->status->value] ?? $booking->status->value }}</span>
                                <span class="d-inline d-sm-none">{{ substr($labelMap[$booking->status->value] ?? $booking->status->value, 0, 1) }}</span>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('bookings.destroy', $booking) }}" class="d-inline" onsubmit="return confirm('Delete this booking?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            @if(request()->hasAny(['search', 'status', 'property_id', 'date_from', 'date_to']))
                                No bookings found matching your filters.
                            @else
                                No bookings found.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($bookings->hasPages())
    <div class="card-footer">{{ $bookings->links() }}</div>
    @endif
</div>

<!-- Bulk Check-In Modal -->
<div class="modal fade" id="bulkCheckinModal" tabindex="-1" aria-labelledby="bulkCheckinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('bookings.bulk-check-in') }}" id="bulkCheckinForm">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-primary" id="bulkCheckinModalLabel">
                        <i class="fas fa-users me-2"></i>Check-In Rombongan (<span id="modalRoomsCount">0</span> Kamar)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden booking IDs container -->
                    <div id="bulkBookingIdsContainer"></div>

                    <!-- Room preview list -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Daftar Kamar Terpilih</label>
                        <div class="p-2 bg-light border rounded d-flex flex-wrap gap-1 overflow-auto" style="max-height: 120px;" id="bulkSelectedRoomsList">
                            <!-- Dynamic Badges -->
                        </div>
                    </div>

                    <!-- Step 1: Phone -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="bulkPhone">
                            <i class="fab fa-whatsapp text-success me-1"></i> Nomor WhatsApp PIC / Perusahaan
                        </label>
                        <input type="text" class="form-control" id="bulkPhone" name="phone" placeholder="Contoh: 6281234567890">
                        <div class="form-text">
                            Nomor ini akan otomatis tersimpan sebagai kontak WhatsApp ke <strong>seluruh kamar rombongan yang dipilih</strong>.
                        </div>

                        {{-- Shortcuts for phone --}}
                        <div class="mt-2" id="bulkPhoneShortcuts">
                            <button type="button" class="btn btn-sm btn-outline-primary d-none me-1 mb-1 btn-bulk-quick-phone" id="btnBulkLastPhone" data-phone="">
                                <i class="fab fa-whatsapp me-1 text-success"></i> Gunakan No. Terakhir: <strong id="lblBulkLastPhone"></strong>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info d-none me-1 mb-1 btn-bulk-quick-phone" id="btnBulkGuestPhone" data-phone="">
                                <i class="fas fa-building me-1"></i> Gunakan No. dari Tamu Kamar: <strong id="lblBulkGuestPhone"></strong>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Facilities -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small text-muted text-uppercase mb-0">Paket Fasilitas Rombongan</label>
                        </div>
                        <p class="text-muted small mb-2">Fasilitas yang dipilih di bawah ini akan diterapkan secara seragam ke seluruh kamar rombongan.</p>

                        @php
                            $standardFacilities = ($facilityTemplates ?? collect())->reject(fn($f) => $f->isDinner());
                            $dinnerFacilities = ($facilityTemplates ?? collect())->filter(fn($f) => $f->isDinner());
                            $defaultDinner = $dinnerFacilities->firstWhere('code', \App\Models\FacilityTemplate::DEFAULT_DINNER_CODE) ?? $dinnerFacilities->first();
                            $defaultDinnerId = $defaultDinner?->id;
                        @endphp

                        @if(!empty($facilityTemplates) && $facilityTemplates->isNotEmpty())
                            <div class="form-check mb-3 p-2 bg-light border rounded">
                                <input class="form-check-input" type="checkbox" id="bulkSelectAllFacilities" checked>
                                <label class="form-check-label fw-bold text-primary" for="bulkSelectAllFacilities">
                                    <i class="fas fa-check-double me-2"></i>Pilih Semua Fasilitas Standar + Dinner
                                </label>
                            </div>
                        @endif

                        @if($standardFacilities->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-1">Fasilitas Standar</label>
                                <div class="bg-light border rounded p-3">
                                    @foreach($standardFacilities as $ft)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input bulk-facility-checkbox bulk-standard-checkbox" 
                                                   type="checkbox" 
                                                   name="facility_template_ids[]" 
                                                   value="{{ $ft->id }}" 
                                                   id="bulk_facility_{{ $ft->id }}" 
                                                   checked>
                                            <label class="form-check-label" for="bulk_facility_{{ $ft->id }}">
                                                {{ $ft->name }}
                                                @if($ft->isOneTime())
                                                    <span class="badge bg-secondary text-white ms-1">One-Time</span>
                                                @endif
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
                                    <span class="badge bg-warning text-dark"><i class="fas fa-info-circle me-1"></i>Maks. 1</span>
                                </div>
                                <div class="alert alert-warning py-1 px-2 small mb-2 text-muted">
                                    <i class="fas fa-info-circle me-1 text-primary"></i> Tamu rombongan hanya dapat memilih salah satu paket Dinner saat Check-In.
                                </div>
                                <div class="bg-light border border-warning rounded p-3">
                                    @foreach($dinnerFacilities as $ft)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input bulk-facility-checkbox bulk-dinner-checkbox" 
                                                   type="checkbox" 
                                                   name="facility_template_ids[]" 
                                                   value="{{ $ft->id }}" 
                                                   id="bulk_facility_{{ $ft->id }}" 
                                                   data-code="{{ $ft->code }}"
                                                   @checked($ft->id === $defaultDinnerId)>
                                            <label class="form-check-label fw-semibold" for="bulk_facility_{{ $ft->id }}">
                                                {{ $ft->name }}
                                                @if($ft->code === 'DINNER-BBQ')
                                                    <span class="badge bg-primary text-white ms-1">Default BBQ</span>
                                                @elseif($ft->code === 'DINNER100K')
                                                    <span class="badge bg-info text-white ms-1">Voucher Resto 100K</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-semibold" id="btnSubmitBulk">
                        <i class="fas fa-check-circle me-1"></i> Konfirmasi Check-In (<span id="btnSubmitCount">0</span> Kamar)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    const selectAllCheckbox = document.getElementById('selectAllBookings');
    const rowCheckboxes = document.querySelectorAll('.booking-select-checkbox');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const bulkSelectedBadge = document.getElementById('bulkSelectedBadge');
    const bulkSelectedPreview = document.getElementById('bulkSelectedPreview');
    const btnDeselectAll = document.getElementById('btnDeselectAll');
    
    // Modal elements
    const bulkModal = document.getElementById('bulkCheckinModal');
    const modalRoomsCount = document.getElementById('modalRoomsCount');
    const btnSubmitCount = document.getElementById('btnSubmitCount');
    const bulkBookingIdsContainer = document.getElementById('bulkBookingIdsContainer');
    const bulkSelectedRoomsList = document.getElementById('bulkSelectedRoomsList');
    const bulkPhone = document.getElementById('bulkPhone');
    const btnBulkLastPhone = document.getElementById('btnBulkLastPhone');
    const lblBulkLastPhone = document.getElementById('lblBulkLastPhone');
    const btnBulkGuestPhone = document.getElementById('btnBulkGuestPhone');
    const lblBulkGuestPhone = document.getElementById('lblBulkGuestPhone');
    const bulkCheckinForm = document.getElementById('bulkCheckinForm');

    function updateBulkState() {
        const checkedBoxes = Array.from(rowCheckboxes).filter(cb => cb.checked);
        const count = checkedBoxes.length;

        if (bulkSelectedBadge) bulkSelectedBadge.textContent = count;
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = rowCheckboxes.length > 0 && checkedBoxes.length === rowCheckboxes.length;
            selectAllCheckbox.indeterminate = count > 0 && count < rowCheckboxes.length;
        }

        if (count > 0) {
            bulkActionBar.style.display = 'block';
            const previews = checkedBoxes.slice(0, 3).map(cb => cb.dataset.room || cb.dataset.reference);
            let previewText = previews.join(', ');
            if (count > 3) {
                previewText += ` (+${count - 3} lainnya)`;
            }
            if (bulkSelectedPreview) bulkSelectedPreview.textContent = `(${previewText})`;
        } else {
            bulkActionBar.style.display = 'none';
            if (bulkSelectedPreview) bulkSelectedPreview.textContent = '';
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            rowCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateBulkState();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkState);
    });

    if (btnDeselectAll) {
        btnDeselectAll.addEventListener('click', function () {
            rowCheckboxes.forEach(cb => {
                cb.checked = false;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
            updateBulkState();
        });
    }

    // Modal populate event
    if (bulkModal) {
        bulkModal.addEventListener('show.bs.modal', function () {
            const checkedBoxes = Array.from(rowCheckboxes).filter(cb => cb.checked);
            const count = checkedBoxes.length;

            if (modalRoomsCount) modalRoomsCount.textContent = count;
            if (btnSubmitCount) btnSubmitCount.textContent = count;

            // Clear and repopulate hidden inputs & preview
            if (bulkBookingIdsContainer) bulkBookingIdsContainer.innerHTML = '';
            if (bulkSelectedRoomsList) bulkSelectedRoomsList.innerHTML = '';

            let foundGuestPhone = '';

            checkedBoxes.forEach(cb => {
                // Add hidden input
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'booking_ids[]';
                hidden.value = cb.value;
                bulkBookingIdsContainer.appendChild(hidden);

                // Add preview badge
                const badge = document.createElement('span');
                badge.className = 'badge bg-white text-dark border p-2';
                badge.innerHTML = `<i class="fas fa-bed me-1 text-primary"></i><strong>${cb.dataset.room || cb.dataset.reference}</strong> <span class="text-muted">(${cb.dataset.guest || 'N/A'})</span>`;
                bulkSelectedRoomsList.appendChild(badge);

                if (!foundGuestPhone && cb.dataset.phone) {
                    foundGuestPhone = cb.dataset.phone;
                }
            });

            // Handle guest phone shortcut
            if (foundGuestPhone && btnBulkGuestPhone && lblBulkGuestPhone) {
                lblBulkGuestPhone.textContent = foundGuestPhone;
                btnBulkGuestPhone.dataset.phone = foundGuestPhone;
                btnBulkGuestPhone.classList.remove('d-none');
                if (bulkPhone && !bulkPhone.value) {
                    bulkPhone.value = foundGuestPhone;
                }
            } else if (btnBulkGuestPhone) {
                btnBulkGuestPhone.classList.add('d-none');
            }

            // Handle last check-in phone from localStorage
            try {
                const lastPhone = localStorage.getItem('last_checkin_phone');
                if (lastPhone && btnBulkLastPhone && lblBulkLastPhone) {
                    lblBulkLastPhone.textContent = lastPhone;
                    btnBulkLastPhone.dataset.phone = lastPhone;
                    btnBulkLastPhone.classList.remove('d-none');
                    if (bulkPhone && !bulkPhone.value) {
                        bulkPhone.value = lastPhone;
                    }
                } else if (btnBulkLastPhone) {
                    btnBulkLastPhone.classList.add('d-none');
                }
            } catch (e) {}
        });
    }

    // Quick phone buttons in bulk modal
    document.querySelectorAll('.btn-bulk-quick-phone').forEach(btn => {
        btn.addEventListener('click', function () {
            const phone = this.dataset.phone;
            if (phone && bulkPhone) {
                bulkPhone.value = phone;
                bulkPhone.focus();
            }
        });
    });

    // Facility selection logic in Bulk Modal
    const bulkSelectAllFacilities = document.getElementById('bulkSelectAllFacilities');
    const bulkStandardCheckboxes = document.querySelectorAll('.bulk-standard-checkbox');
    const bulkDinnerCheckboxes = document.querySelectorAll('.bulk-dinner-checkbox');

    // Mutually exclusive dinner checkboxes
    bulkDinnerCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked) {
                bulkDinnerCheckboxes.forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
            updateBulkSelectAllState();
        });
    });

    bulkStandardCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkSelectAllState);
    });

    function updateBulkSelectAllState() {
        if (!bulkSelectAllFacilities) return;
        const allStandardChecked = Array.from(bulkStandardCheckboxes).every(cb => cb.checked);
        const hasOneDinnerChecked = Array.from(bulkDinnerCheckboxes).some(cb => cb.checked);
        bulkSelectAllFacilities.checked = allStandardChecked && (bulkDinnerCheckboxes.length === 0 || hasOneDinnerChecked);
    }

    if (bulkSelectAllFacilities) {
        bulkSelectAllFacilities.addEventListener('change', function () {
            const check = this.checked;
            bulkStandardCheckboxes.forEach(cb => {
                cb.checked = check;
            });
            if (check) {
                let dinnerChecked = false;
                bulkDinnerCheckboxes.forEach(cb => {
                    if (!dinnerChecked && (cb.dataset.code === 'DINNER-BBQ' || cb === bulkDinnerCheckboxes[0])) {
                        cb.checked = true;
                        dinnerChecked = true;
                    } else {
                        cb.checked = false;
                    }
                });
            } else {
                bulkDinnerCheckboxes.forEach(cb => {
                    cb.checked = false;
                });
            }
        });
    }

    // Save phone to localStorage on bulk submit
    if (bulkCheckinForm) {
        bulkCheckinForm.addEventListener('submit', function (e) {
            const checkedBoxes = Array.from(rowCheckboxes).filter(cb => cb.checked);
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Pilih setidaknya 1 kamar untuk di-check-in.');
                return;
            }

            const phone = bulkPhone.value.trim();
            if (phone) {
                try {
                    localStorage.setItem('last_checkin_phone', phone);
                } catch (err) {}
            }
        });
    }
})();
</script>
@endpush
@endsection
