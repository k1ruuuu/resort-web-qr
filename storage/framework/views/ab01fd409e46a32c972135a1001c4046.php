
<?php $__env->startSection('title', 'New Booking'); ?>
<?php $__env->startSection('page_title', 'New Booking'); ?>
<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo e(route('bookings.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Property</label>
                    <select name="property_id" class="form-select" required>
                        <?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($property->id); ?>"><?php echo e($property->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Guest</label>
                    <select name="guest_id" class="form-select" required>
                        <?php $__currentLoopData = $guests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($guest->id); ?>"><?php echo e($guest->full_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Room</label>
                    <select name="room_id" class="form-select <?php $__errorArgs = ['room_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <option value="">Select room...</option>
                        <?php $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($room->id); ?>" <?php if(old('room_id') == $room->id): ?> selected <?php endif; ?>>
                                <?php echo e($room->number); ?> (<?php echo e($room->property->name); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['room_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Arrangement Code (optional)</label>
                    <input type="text" name="arrangement_code" class="form-control" placeholder="e.g. RPCGLP26" value="<?php echo e(old('arrangement_code')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Check-in</label>
                    <input type="date" name="check_in" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Check-out</label>
                    <input type="date" name="check_out" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Adults</label>
                    <input type="number" name="adults" value="1" min="1" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Children</label>
                    <input type="number" name="children" value="0" min="0" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Extra beds</label>
                    <input type="number" name="extra_beds" value="0" min="0" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Facilities (optional)</label>
                    <?php if(!empty($facilityTemplates) && count($facilityTemplates) > 0): ?>
                        <!-- Select All Checkbox -->
                        <div class="form-check mb-2 p-2 bg-light border rounded">
                            <input class="form-check-input" type="checkbox" id="selectAllFacilities">
                            <label class="form-check-label fw-bold text-primary" for="selectAllFacilities">
                                <i class="fas fa-check-double me-2"></i>Select All Facilities
                            </label>
                        </div>
                    <?php endif; ?>
                    
                    <?php $__empty_1 = true; $__currentLoopData = $facilityTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="form-check">
                            <input class="form-check-input facility-checkbox" type="checkbox" name="facilities[<?php echo e($index); ?>][facility_template_id]" value="<?php echo e($facility->id); ?>" id="facility_<?php echo e($facility->id); ?>">
                            <label class="form-check-label" for="facility_<?php echo e($facility->id); ?>"><?php echo e($facility->name); ?></label>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-muted small">No facility templates configured.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save Booking</button>
            </div>
        </form>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script nonce="<?php echo e($cspNonce); ?>">
    // Handle "Select All" checkbox functionality
    const selectAllCheckbox = document.getElementById('selectAllFacilities');
    
    if (selectAllCheckbox) {
        const facilityCheckboxes = document.querySelectorAll('.facility-checkbox');
        
        // Handle "Select All" checkbox change
        selectAllCheckbox.addEventListener('change', function() {
            facilityCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
        
        // Handle individual checkbox changes
        facilityCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                // If any checkbox is unchecked, uncheck "Select All"
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    // If all checkboxes are checked, check "Select All"
                    const allChecked = Array.from(facilityCheckboxes).every(checkbox => checkbox.checked);
                    if (allChecked) {
                        selectAllCheckbox.checked = true;
                    }
                }
            });
        });
    }
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views\bookings\create.blade.php ENDPATH**/ ?>