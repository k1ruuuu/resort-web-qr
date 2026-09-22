@extends('layouts.app')
@section('title', 'New Booking')
@section('page_title', 'New Booking')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    /* Select2 Forest Theme Styling */
    .select2-container--bootstrap-5 .select2-selection {
        border-color: #ced4da;
        border-radius: 0.375rem;
        min-height: 38px;
        display: flex;
        align-items: center;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #3a8b66 !important;
        box-shadow: 0 0 0 0.25rem rgba(58, 139, 102, 0.25) !important;
    }
    .select2-container--bootstrap-5 .select2-dropdown {
        border-color: #3a8b66;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        border-radius: 0.375rem;
        overflow: hidden;
    }
    .select2-container--bootstrap-5 .select2-search__field:focus {
        border-color: #3a8b66 !important;
        box-shadow: 0 0 0 0.2rem rgba(58, 139, 102, 0.2) !important;
    }
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
        background-color: #2c5e43 !important;
        color: #ffffff !important;
    }
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] .text-dark,
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] .text-muted,
    .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] i {
        color: #e8f5ee !important;
    }
    .select2-container--bootstrap-5 .select2-results__option--selected {
        background-color: #e8f5ee !important;
        color: #1c3f2d !important;
        font-weight: 600;
    }
    .select2-guest-result {
        padding: 3px 0;
    }
    .select2-guest-name {
        font-weight: 600;
        font-size: 0.92rem;
    }
    .select2-guest-meta {
        font-size: 0.78rem;
        color: #6c757d;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 2px;
    }
</style>
@endpush
@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('bookings.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="property_id">Property</label>
                    <select name="property_id" id="property_id" class="form-select" required>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}">{{ $property->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0" for="guest_id">Guest <span class="text-danger">*</span></label>
                        @can('guests.manage')
                            <a href="{{ route('guests.create') }}" target="_blank" class="small text-decoration-none text-success fw-semibold" title="Create a new guest in new tab">
                                <i class="fas fa-user-plus me-1"></i>+ Add New Guest
                            </a>
                        @endcan
                    </div>
                    <select name="guest_id" id="guest_id" class="form-select @error('guest_id') is-invalid @enderror" required data-placeholder="Search guest by name, phone, or email...">
                        <option value=""></option>
                        @foreach($guests as $guest)
                            @php
                                $details = array_filter([$guest->phone, $guest->email]);
                                $detailText = !empty($details) ? ' (' . implode(' • ', $details) . ')' : '';
                            @endphp
                            <option value="{{ $guest->id }}" 
                                @selected(old('guest_id') == $guest->id)
                                data-name="{{ $guest->full_name }}"
                                data-phone="{{ $guest->phone ?? '' }}"
                                data-email="{{ $guest->email ?? '' }}"
                                data-document="{{ $guest->document_id ?? '' }}">
                                {{ $guest->full_name }}{{ $detailText }}
                            </option>
                        @endforeach
                    </select>
                    @error('guest_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="room_id">Room</label>
                    <select name="room_id" class="form-select @error('room_id') is-invalid @enderror">
                        <option value="">Select room...</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @if(old('room_id') == $room->id) selected @endif>
                                {{ $room->number }} ({{ $room->property->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="arrangement_code">Arrangement Code (optional)</label>
                    <input type="text" name="arrangement_code" id="arrangement_code" class="form-control" placeholder="e.g. RPCGLP26" value="{{ old('arrangement_code') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="check_in">Check-in</label>
                    <input type="date" name="check_in" id="check_in" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="check_out">Check-out</label>
                    <input type="date" name="check_out" id="check_out" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="adults">Adults</label>
                    <input type="number" name="adults" id="adults" value="1" min="1" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="children">Children</label>
                    <input type="number" name="children" id="children" value="0" min="0" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="extra_beds">Extra beds</label>
                    <input type="number" name="extra_beds" id="extra_beds" value="0" min="0" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold mb-2">Facilities (optional)</label>
                    
                    @php
                        $facilityCollection = collect($facilityTemplates ?? []);
                        $standardFacilities = $facilityCollection->reject(fn($f) => $f->isDinner());
                        $dinnerFacilities = $facilityCollection->filter(fn($f) => $f->isDinner());
                    @endphp

                    @if($facilityCollection->isNotEmpty())
                        <!-- Select All Checkbox -->
                        <div class="form-check mb-3 p-2 bg-light border rounded">
                            <input class="form-check-input" type="checkbox" id="selectAllFacilities">
                            <label class="form-check-label fw-bold text-primary" for="selectAllFacilities">
                                <i class="fas fa-check-double me-2"></i>Select All Standard Facilities + Dinner
                            </label>
                        </div>
                    @endif

                    @if($standardFacilities->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted text-uppercase mb-2">Standard Inclusions</label>
                            <div class="bg-light border rounded p-3">
                                @foreach($standardFacilities as $facility)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input facility-checkbox standard-facility-checkbox" 
                                               type="checkbox" 
                                               name="facilities[{{ $loop->index }}][facility_template_id]" 
                                               value="{{ $facility->id }}" 
                                               id="facility_{{ $facility->id }}">
                                        <label class="form-check-label" for="facility_{{ $facility->id }}">{{ $facility->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($dinnerFacilities->isNotEmpty())
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-0">Dinner Package (Choose Max 1)</label>
                                <span class="badge bg-warning text-dark"><i class="fas fa-info-circle me-1"></i>Exclusive</span>
                            </div>
                            <div class="alert alert-warning py-1 px-2 small mb-2 text-muted">
                                <i class="fas fa-exchange-alt me-1 text-primary"></i> Tamu hanya dapat memilih salah satu paket Dinner. Kuota dinner dapat ditukar setelah check-in.
                            </div>
                            <div class="bg-light border border-warning rounded p-3">
                                @foreach($dinnerFacilities as $facility)
                                    @php
                                        $offset = $standardFacilities->count() + $loop->index;
                                    @endphp
                                    <div class="form-check mb-2">
                                        <input class="form-check-input facility-checkbox dinner-facility-checkbox" 
                                               type="checkbox" 
                                               name="facilities[{{ $offset }}][facility_template_id]" 
                                               value="{{ $facility->id }}" 
                                               id="facility_{{ $facility->id }}"
                                               data-code="{{ $facility->code }}">
                                        <label class="form-check-label fw-semibold" for="facility_{{ $facility->id }}">
                                            {{ $facility->name }}
                                            @if($facility->code === 'DINNER-BBQ')
                                                <span class="badge bg-primary text-white ms-1">Default BBQ</span>
                                            @elseif($facility->code === 'DINNER100K')
                                                <span class="badge bg-info text-white ms-1">Voucher Resto 100K</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($facilityCollection->isEmpty())
                        <p class="text-muted small">No facility templates configured.</p>
                    @endif
                    @error('facilities')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save Booking</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script nonce="{{ $cspNonce }}">
$(document).ready(function() {
    // Custom Matcher function for searching Guest by Name, Phone, Email, or Document ID
    function matchGuest(params, data) {
        if ($.trim(params.term) === '') {
            return data;
        }

        if (typeof data.text === 'undefined') {
            return null;
        }

        const term = params.term.toLowerCase().trim();
        const text = (data.text || '').toLowerCase();
        
        const phone = (data.element && data.element.dataset.phone) ? data.element.dataset.phone.toLowerCase() : '';
        const email = (data.element && data.element.dataset.email) ? data.element.dataset.email.toLowerCase() : '';
        const doc = (data.element && data.element.dataset.document) ? data.element.dataset.document.toLowerCase() : '';

        if (text.indexOf(term) > -1 || phone.indexOf(term) > -1 || email.indexOf(term) > -1 || doc.indexOf(term) > -1) {
            return data;
        }

        return null;
    }

    // Custom template for rendering options in dropdown list
    function formatGuestOption(guest) {
        if (!guest.id) {
            return guest.text;
        }

        const name = (guest.element && guest.element.dataset.name) ? guest.element.dataset.name : guest.text.split('(')[0].trim();
        const phone = guest.element ? guest.element.dataset.phone : '';
        const email = guest.element ? guest.element.dataset.email : '';
        const doc = guest.element ? guest.element.dataset.document : '';

        const $wrapper = $('<div class="select2-guest-result"></div>');
        const $nameEl = $('<div class="select2-guest-name text-dark"></div>').text(name);
        $wrapper.append($nameEl);

        const $meta = $('<div class="select2-guest-meta"></div>');
        let hasMeta = false;

        if (phone) {
            $meta.append($('<span><i class="fas fa-phone-alt me-1 text-success"></i></span>').append(document.createTextNode(phone)));
            hasMeta = true;
        }
        if (email) {
            $meta.append($('<span><i class="fas fa-envelope me-1 text-primary"></i></span>').append(document.createTextNode(email)));
            hasMeta = true;
        }
        if (doc) {
            $meta.append($('<span><i class="fas fa-id-card me-1 text-secondary"></i></span>').append(document.createTextNode(doc)));
            hasMeta = true;
        }

        if (hasMeta) {
            $wrapper.append($meta);
        }

        return $wrapper;
    }

    // Custom template for displaying the selected option in the field
    function formatGuestSelection(guest) {
        if (!guest.id) {
            return guest.text;
        }
        const name = (guest.element && guest.element.dataset.name) ? guest.element.dataset.name : guest.text.split('(')[0].trim();
        const phone = guest.element ? guest.element.dataset.phone : '';
        return phone ? name + ' (' + phone + ')' : name;
    }

    // Initialize Select2 on #guest_id
    const $guestSelect = $('#guest_id');
    $guestSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'Search guest by name, phone, or email...',
        allowClear: true,
        width: '100%',
        matcher: matchGuest,
        templateResult: formatGuestOption,
        templateSelection: formatGuestSelection
    });

    // Handle HTML5 validation and invalid event smoothly
    $guestSelect.on('invalid', function(e) {
        e.preventDefault();
        $(this).next('.select2-container').find('.select2-selection').addClass('border-danger');
        $guestSelect.select2('open');
    });

    $guestSelect.on('change select2:select', function() {
        $(this).next('.select2-container').find('.select2-selection').removeClass('border-danger');
    });

    // Form submit validation check for Select2
    $guestSelect.closest('form').on('submit', function(e) {
        if (!$guestSelect.val()) {
            e.preventDefault();
            $guestSelect.next('.select2-container').find('.select2-selection').addClass('border-danger');
            $guestSelect.select2('open');
            return false;
        }
    });

    // Handle exclusive dinner checkboxes and "Select All" functionality
    const selectAllCheckbox = document.getElementById('selectAllFacilities');
    const standardCheckboxes = document.querySelectorAll('.standard-facility-checkbox');
    const dinnerCheckboxes = document.querySelectorAll('.dinner-facility-checkbox');

    // Mutually exclusive dinner checkboxes
    dinnerCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
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
        cb.addEventListener('change', function() {
            updateSelectAllState();
        });
    });

    function updateSelectAllState() {
        if (!selectAllCheckbox) return;
        const allStandardChecked = Array.from(standardCheckboxes).every(cb => cb.checked);
        const hasOneDinnerChecked = Array.from(dinnerCheckboxes).some(cb => cb.checked);
        selectAllCheckbox.checked = allStandardChecked && (dinnerCheckboxes.length === 0 || hasOneDinnerChecked);
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
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
    }
});
</script>
@endpush
@endsection
