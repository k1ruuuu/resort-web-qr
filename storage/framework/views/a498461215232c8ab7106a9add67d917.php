
<?php $__env->startSection('title', 'Guest Vouchers'); ?>
<?php $__env->startSection('page_title', 'Guest Vouchers'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .facility-checkbox:checked + .btn-outline-primary {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
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
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<!-- Search/Filter Card -->
<div class="card mb-3">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Search & Filter Vouchers</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('vouchers.index')); ?>" id="filterForm">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="QR Code, Guest Name, Booking Code..." 
                           value="<?php echo e(request('search')); ?>">
                    <small class="form-text text-muted">Search by QR code, guest name, or booking reference</small>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Active</option>
                        <option value="redeemed" <?php echo e(request('status') === 'redeemed' ? 'selected' : ''); ?>>Redeemed</option>
                        <option value="expired" <?php echo e(request('status') === 'expired' ? 'selected' : ''); ?>>Expired</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="standard" <?php echo e(request('category') === 'standard' ? 'selected' : ''); ?>>Standard</option>
                        <option value="temporary" <?php echo e(request('category') === 'temporary' ? 'selected' : ''); ?>>Temporary</option>
                    </select>
                </div>

                <!-- Property Filter -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Property</label>
                    <select name="property_id" class="form-select">
                        <option value="">All Properties</option>
                        <?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($property->id); ?>" <?php echo e(request('property_id') == $property->id ? 'selected' : ''); ?>>
                                <?php echo e($property->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo e(request('date_from')); ?>">
                </div>

                <!-- Date To -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo e(request('date_to')); ?>">
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                    <a href="<?php echo e(route('vouchers.index')); ?>" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Reset
                    </a>
                    <?php if(request()->hasAny(['search', 'status', 'category', 'property_id', 'date_from', 'date_to'])): ?>
                        <span class="badge bg-info ms-2">
                            <i class="fas fa-info-circle"></i> Filters Active
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </form>
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
        <form method="POST" action="<?php echo e(route('vouchers.generate')); ?>" id="tempVoucherForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="category" value="temporary">
            <input type="hidden" name="facility_selection" id="facilitySelectionInput" value="single">
            
            <div class="row g-3">
                <!-- Property Selection -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Property <span class="text-danger">*</span></label>
                    <select name="property_id" id="propertySelect" class="form-select" required>
                        <option value="">-- Select Property --</option>
                        <?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($property->id); ?>"><?php echo e($property->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <small class="form-text text-muted">Choose the property for this voucher</small>
                </div>

                <!-- Guest Name -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Guest Name <span class="text-danger">*</span></label>
                    <input type="text" name="guest_name" class="form-control" placeholder="Enter guest name" required>
                    <small class="form-text text-muted">Full name of the temporary guest</small>
                </div>

                <!-- Pax Limit -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Pax Limit <span class="text-danger">*</span></label>
                    <input type="number" name="pax_limit" class="form-control" value="1" min="1" required>
                    <small class="form-text text-muted">Maximum number of people per redemption</small>
                </div>

                <!-- Expiration Type and Value -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Expiration Type <span class="text-danger">*</span></label>
                    <select name="expiration_type" id="expirationType" class="form-select" required>
                        <option value="hour">Hours from now</option>
                        <option value="date">Specific date</option>
                    </select>
                    <small class="form-text text-muted">Choose expiration method</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Expiration Value <span class="text-danger">*</span></label>
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
            Total: <?php echo e($vouchers->total()); ?> | Showing: <?php echo e($vouchers->count()); ?>

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
                <?php $__empty_1 = true; $__currentLoopData = $vouchers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <strong><?php echo e($voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest')); ?></strong>
                            <?php if($voucher->category === 'temporary'): ?>
                                <br><small class="badge bg-info">Temporary</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($voucher->booking?->room_label ?? $voucher->booking?->room?->label ?? 'TEMP'); ?></td>
                        <td>
                            <?php if($voucher->booking): ?>
                                <?php echo e($voucher->booking->check_in->format('Y-m-d')); ?> – <?php echo e($voucher->booking->check_out->format('Y-m-d')); ?>

                            <?php else: ?>
                                <strong>Expires:</strong> <?php echo e($voucher->expires_at ? $voucher->expires_at->format('Y-m-d H:i') : 'N/A'); ?>

                            <?php endif; ?>
                        </td>
                        <td><code class="text-mono small"><?php echo e($voucher->qr_code); ?></code></td>
                        <td><code class="text-mono text-muted small"><?php echo e(substr($voucher->secure_token, 0, 12)); ?>...</code></td>
                        <td>
                            <?php
                                $statusColors = [
                                    'active' => 'success',
                                    'redeemed' => 'secondary',
                                    'expired' => 'danger',
                                ];
                                $color = $statusColors[$voucher->status->value] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo e($color); ?>">
                                <?php echo e(ucfirst($voucher->status->value)); ?>

                            </span>
                        </td>
                        <td>
                            <a href="<?php echo e(route('vouchers.show', $voucher)); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-qrcode"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No vouchers found</p>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($vouchers->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($vouchers->links()); ?>

        </div>
    <?php endif; ?>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // Facility templates grouped by property
    const facilityTemplates = <?php echo json_encode($facilityTemplates, 15, 512) ?>;
    
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
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views/vouchers/index.blade.php ENDPATH**/ ?>