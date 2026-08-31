@extends('layouts.app')
@section('title', 'Guest Vouchers')
@section('page_title', 'Guest Vouchers')

@push('styles')
<style>
    .facility-checkbox:checked + .btn-outline-primary {
        background-color: #2d6a4f;
        color: white;
        border-color: #2d6a4f;
        font-weight: 600;
    }
    .facility-checkbox + .btn-outline-primary {
        min-width: 180px;
        text-align: left;
        white-space: normal;
    }
    .form-label.fw-bold {
        color: #495057;
        font-size: 0.95rem;
    }
    .card-header h5 {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    code.text-mono {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')

<!-- Search/Filter Card -->
<div class="card mb-3">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Search & Filter Vouchers</h5>
        <button class="btn btn-sm btn-light d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#voucherFilterCollapse" aria-expanded="true">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>
    <div class="collapse collapse-md-show" id="voucherFilterCollapse">
    <div class="card-body">
        <form method="GET" action="{{ route('vouchers.index') }}" id="filterForm">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="QR Code, Guest Name, Booking Code..." 
                           value="{{ request('search') }}">
                    <small class="form-text text-muted">Search by QR code, guest name, or booking reference</small>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="redeemed" {{ request('status') === 'redeemed' ? 'selected' : '' }}>Redeemed</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="standard" {{ request('category') === 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="temporary" {{ request('category') === 'temporary' ? 'selected' : '' }}>Temporary</option>
                    </select>
                </div>

                <!-- Property Filter -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Property</label>
                    <select name="property_id" class="form-select">
                        <option value="">All Properties</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                                {{ $property->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <!-- Date To -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                    <a href="{{ route('vouchers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Reset
                    </a>
                    @if(request()->hasAny(['search', 'status', 'category', 'property_id', 'date_from', 'date_to']))
                        <span class="badge bg-info ms-2">
                            <i class="fas fa-info-circle"></i> Filters Active
                        </span>
                    @endif
                </div>
            </div>
        </form>
    </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0 d-flex justify-content-between align-items-center">
            <span><i class="fas fa-ticket-alt me-2"></i>Generate Temporary QR Voucher</span>
            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#tempVoucherFormCollapse" aria-expanded="false" aria-controls="tempVoucherFormCollapse">
                <i class="fas fa-chevron-down" id="collapseIcon"></i>
            </button>
        </h5>
    </div>
    <div class="collapse" id="tempVoucherFormCollapse">
        <div class="card-body">
        <form method="POST" action="{{ route('vouchers.generate') }}" id="tempVoucherForm">
            @csrf
            <input type="hidden" name="category" value="temporary">
            <input type="hidden" name="facility_selection" id="facilitySelectionInput" value="single">
            
            <div class="row g-3">
                <!-- Property Selection -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Property</label>
                    <select name="property_id" id="propertySelect" class="form-select" required>
                        <option value="">-- Select Property --</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}">{{ $property->name }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Choose the property for this voucher</small>
                </div>

                <!-- Guest Name -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Guest Name</label>
                    <input type="text" name="guest_name" class="form-control" placeholder="Enter guest name" required>
                    <small class="form-text text-muted">Full name of the temporary guest</small>
                </div>

                <!-- Phone Number -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">WhatsApp Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g. 081234567890">
                    <small class="form-text text-muted">Required to send the voucher via WhatsApp</small>
                </div>

                <!-- Pax Limit -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Pax Limit</label>
                    <input type="number" name="pax_limit" class="form-control" value="1" min="1" required>
                    <small class="form-text text-muted">Maximum number of people per redemption</small>
                </div>

                <!-- Expiration Type and Value -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Expiration Type</label>
                    <select name="expiration_type" id="expirationType" class="form-select" required>
                        <option value="hour">Hours from now</option>
                        <option value="date">Specific date</option>
                    </select>
                    <small class="form-text text-muted">Choose expiration method</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Expiration Value</label>
                    <input type="text" name="expiration_value" id="expirationValue" class="form-control" placeholder="e.g., 6" required>
                    <small class="form-text text-muted" id="expirationHelp">Number of hours (e.g., 6 for 6 hours)</small>
                </div>
            </div>

            <!-- Facility Selection Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <label class="form-label fw-bold d-block mb-2">Facility Access <span class="text-danger">*</span></label>
                    <small class="form-text text-muted d-block mb-3">Select one or more facilities, or check "Select All" to include all facilities</small>
                    
                    <!-- Select All Checkbox -->
                    <div class="form-check mb-3 p-2 bg-light border rounded">
                        <input class="form-check-input" type="checkbox" id="selectAllFacilities">
                        <label class="form-check-label fw-bold text-primary" for="selectAllFacilities">
                            <i class="fas fa-check-double me-2"></i>Select All Facilities
                        </label>
                    </div>

                    <!-- Facility Buttons Container -->
                    <div id="facilityCheckboxContainer">
                        <div id="facilityCheckboxes" class="d-flex flex-wrap gap-2">
                            <!-- Facilities will be loaded here dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-qrcode me-2"></i>Generate Temporary Voucher
                    </button>
                </div>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- Vouchers List -->
<div class="card">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Vouchers</h5>
        <span class="badge bg-light text-dark">
            Total: {{ $vouchers->total() }} | Showing: {{ $vouchers->count() }}
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Guest Name</th>
                        <th>Room</th>
                        <th>Stay Dates / Expiration</th>
                        <th>QR Code</th>
                        <th>Secure Token</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($vouchers as $voucher)
                    <tr>
                        <td>
                            <strong>{{ $voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest') }}</strong>
                            @if($voucher->category === 'temporary')
                                <br><small class="badge bg-info">Temporary</small>
                            @endif
                        </td>
                        <td>{{ $voucher->booking?->room_label ?? $voucher->booking?->room?->label ?? 'TEMP' }}</td>
                        <td>
                            @if($voucher->booking)
                                {{ $voucher->booking->check_in->format('Y-m-d') }} – {{ $voucher->booking->check_out->format('Y-m-d') }}
                            @else
                                <strong>Expires:</strong> {{ $voucher->expires_at_local ? $voucher->expires_at_local->format('Y-m-d H:i') : 'N/A' }}
                            @endif
                        </td>
                        <td><code class="text-mono small">{{ substr($voucher->qr_code, 0, 12) }}{{ strlen($voucher->qr_code) > 12 ? '...' : '' }}</code></td>
                        <td><code class="text-mono text-muted small">{{ substr($voucher->secure_token, 0, 12) }}...</code></td>
                        <td>
                            @php
                                $statusColors = [
                                    'active' => 'success',
                                    'redeemed' => 'secondary',
                                    'expired' => 'danger',
                                ];
                                $color = $statusColors[$voucher->status->value] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}">
                                {{ ucfirst($voucher->status->value) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('vouchers.show', $voucher) }}" class="btn btn-sm btn-outline-primary" title="View">
                                <i class="fas fa-qrcode"></i>
                            </a>
                            @can('vouchers.edit')
                                <a href="{{ route('vouchers.edit', $voucher) }}" class="btn btn-sm btn-outline-warning" title="Edit Facilities">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No vouchers found</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($vouchers->hasPages())
        <div class="card-footer">
            {{ $vouchers->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script nonce="{{ $cspNonce }}">
    // Facility templates grouped by property
    const facilityTemplates = @json($facilityTemplates);
    
    // DOM Elements
    const propertySelect = document.getElementById('propertySelect');
    const expirationTypeSelect = document.getElementById('expirationType');
    const expirationValueInput = document.getElementById('expirationValue');
    const expirationHelpText = document.getElementById('expirationHelp');
    const facilityCheckboxContainer = document.getElementById('facilityCheckboxContainer');
    const facilityCheckboxes = document.getElementById('facilityCheckboxes');
    const facilitySelectionInput = document.getElementById('facilitySelectionInput');
    const selectAllCheckbox = document.getElementById('selectAllFacilities');
    
    // Handle collapse icon rotation
    const tempVoucherCollapse = document.getElementById('tempVoucherFormCollapse');
    const collapseIcon = document.getElementById('collapseIcon');
    
    tempVoucherCollapse.addEventListener('show.bs.collapse', function () {
        collapseIcon.classList.remove('fa-chevron-down');
        collapseIcon.classList.add('fa-chevron-up');
    });
    
    tempVoucherCollapse.addEventListener('hide.bs.collapse', function () {
        collapseIcon.classList.remove('fa-chevron-up');
        collapseIcon.classList.add('fa-chevron-down');
    });
    
    // Update expiration help text based on type
    expirationTypeSelect.addEventListener('change', function() {
        if (this.value === 'hour') {
            expirationValueInput.placeholder = 'e.g., 6';
            expirationHelpText.textContent = 'Number of hours (e.g., 6 for 6 hours)';
        } else {
            expirationValueInput.placeholder = 'YYYY-MM-DD';
            expirationHelpText.textContent = 'Date in YYYY-MM-DD format (e.g., 2026-12-31)';
        }
        expirationValueInput.value = '';
    });
    
    // Handle property change - load facilities
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        loadFacilities(propertyId);
    });
    
    // Handle "Select All" checkbox
    selectAllCheckbox.addEventListener('change', function() {
        const individualCheckboxes = document.querySelectorAll('.facility-checkbox');
        
        if (this.checked) {
            // Check all individual facilities
            individualCheckboxes.forEach(cb => {
                cb.checked = true;
                cb.disabled = true;
            });
            facilitySelectionInput.value = 'all';
        } else {
            // Uncheck and enable all individual facilities
            individualCheckboxes.forEach(cb => {
                cb.checked = false;
                cb.disabled = false;
            });
            facilitySelectionInput.value = 'multiple';
        }
    });
    
    function loadFacilities(propertyId) {
        facilityCheckboxes.innerHTML = '';
        selectAllCheckbox.checked = false;
        
        if (!propertyId || !facilityTemplates[propertyId]) {
            facilityCheckboxContainer.style.display = 'none';
            return;
        }
        
        const facilities = facilityTemplates[propertyId];
        
        if (facilities.length === 0) {
            facilityCheckboxes.innerHTML = '<p class="text-muted mb-0">No facilities available for this property</p>';
            facilityCheckboxContainer.style.display = 'block';
            return;
        }
        
        facilityCheckboxContainer.style.display = 'block';
        
        facilities.forEach(facility => {
            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'form-check';
            checkboxDiv.innerHTML = `
                <input class="btn-check facility-checkbox" 
                       type="checkbox" 
                       name="facility_template_ids[]" 
                       value="${facility.id}" 
                       id="facility_${facility.id}" 
                       autocomplete="off">
                <label class="btn btn-outline-primary btn-sm" for="facility_${facility.id}">
                    ${facility.name}
                </label>
            `;
            facilityCheckboxes.appendChild(checkboxDiv);
        });
        
        // Add event listeners to individual checkboxes
        const individualCheckboxes = document.querySelectorAll('.facility-checkbox');
        individualCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                // If any individual checkbox is unchecked, uncheck "Select All"
                if (!this.checked && selectAllCheckbox.checked) {
                    selectAllCheckbox.checked = false;
                }
                
                // If all individual checkboxes are checked, check "Select All"
                const allChecked = Array.from(individualCheckboxes).every(checkbox => checkbox.checked);
                if (allChecked) {
                    selectAllCheckbox.checked = true;
                }
                
                // Update facility selection type
                updateFacilitySelectionType();
            });
        });
    }
    
    function updateFacilitySelectionType() {
        const individualCheckboxes = document.querySelectorAll('.facility-checkbox');
        const checkedCount = Array.from(individualCheckboxes).filter(cb => cb.checked).length;
        
        if (selectAllCheckbox.checked || checkedCount === individualCheckboxes.length) {
            facilitySelectionInput.value = 'all';
        } else if (checkedCount > 1) {
            facilitySelectionInput.value = 'multiple';
        } else if (checkedCount === 1) {
            facilitySelectionInput.value = 'single';
        } else {
            facilitySelectionInput.value = 'multiple';
        }
    }
    
    // Form validation
    document.getElementById('tempVoucherForm').addEventListener('submit', function(e) {
        const propertyId = propertySelect.value;
        
        if (!propertyId) {
            e.preventDefault();
            alert('Please select a property');
            return false;
        }
        
        const selectAll = selectAllCheckbox.checked;
        const checkedBoxes = document.querySelectorAll('.facility-checkbox:checked');
        
        if (!selectAll && checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one facility or check "Select All Facilities"');
            return false;
        }
    });
</script>
@endpush
@endsection
