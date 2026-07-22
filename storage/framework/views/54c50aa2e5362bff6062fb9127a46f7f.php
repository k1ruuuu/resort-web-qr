<?php $__env->startSection('title', 'Edit Voucher Facilities'); ?>
<?php $__env->startSection('page_title', 'Edit Voucher: ' . ($voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest'))); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?php echo e(route('vouchers.update', $voucher)); ?>">
                    <?php echo csrf_field(); ?>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Modify the facilities and addition assigned to this voucher.
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Guest Name:</strong></p>
                            <p class="text-muted"><?php echo e($voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest')); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Status:</strong></p>
                            <p><span class="badge bg-<?php echo e($voucher->status->value === 'active' ? 'success' : 'secondary'); ?>"><?php echo e(ucfirst($voucher->status->value)); ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Room:</strong></p>
                            <p class="text-muted"><?php echo e($voucher->booking?->room_label ?? $voucher->booking?->room?->label ?? 'Temporary'); ?> (<?php echo e($voucher->booking?->room?->code ?? 'TEMP'); ?>)</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Booking Code:</strong></p>
                            <p class="text-muted"><?php echo e($voucher->booking?->booking_code ?? $voucher->booking?->reference ?? 'N/A'); ?></p>
                        </div>
                        <?php if($voucher->booking): ?>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Stay Dates:</strong></p>
                            <p class="text-muted"><?php echo e($voucher->booking->check_in->format('Y-m-d')); ?> – <?php echo e($voucher->booking->check_out->format('Y-m-d')); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Total Pax (Booking):</strong></p>
                            <p class="text-muted"><?php echo e($voucher->booking->total_pax + $voucher->booking->extra_beds); ?></p>
                        </div>
                        <?php else: ?>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Expires At:</strong></p>
                            <p class="text-muted"><?php echo e($voucher->expires_at ? $voucher->expires_at->format('Y-m-d H:i') : 'N/A'); ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Property:</strong></p>
                            <p class="text-muted"><?php echo e($voucher->property?->name ?? $voucher->booking?->property?->name ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Addition</label>
                        <p class="form-text text-muted mt-0 mb-2">Extra pax to add on top of the booking's Total Pax. Leave empty for no addition.</p>
                        <input type="number" name="addition" class="form-control <?php $__errorArgs = ['addition'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               value="<?php echo e(old('addition', $voucher->addition)); ?>" min="0" max="50" placeholder="0">
                        <?php $__errorArgs = ['addition'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Facility Access</label>
                        <p class="form-text text-muted mt-0 mb-2">Mark each facility as Granted or Not Granted. Changes take effect on save.</p>

                        <?php if($facilityTemplates->isEmpty()): ?>
                            <div class="alert alert-warning">No active facilities available for this property.</div>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php $__currentLoopData = $facilityTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $isGranted = in_array($facility->id, $currentFacilityIds); ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="fw-bold small mb-1"><?php echo e($facility->name); ?></div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio"
                                                   name="facility_status[<?php echo e($facility->id); ?>]"
                                                   value="granted"
                                                   id="facility_<?php echo e($facility->id); ?>_granted"
                                                   <?php echo e($isGranted ? 'checked' : ''); ?>>
                                            <label class="form-check-label small text-success" for="facility_<?php echo e($facility->id); ?>_granted">Granted</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio"
                                                   name="facility_status[<?php echo e($facility->id); ?>]"
                                                   value="not_granted"
                                                   id="facility_<?php echo e($facility->id); ?>_not_granted"
                                                   <?php echo e(!$isGranted ? 'checked' : ''); ?>>
                                            <label class="form-check-label small text-muted" for="facility_<?php echo e($facility->id); ?>_not_granted">Not Granted</label>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                        <?php $__errorArgs = ['facility_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mb-3" id="addition-facility-section" style="display: <?php echo e(($voucher->addition ?? 0) > 0 ? 'block' : 'none'); ?>">
                        <label class="form-label fw-bold">Apply Addition To</label>
                        <p class="form-text text-muted mt-0 mb-2">Select which facilities get the extra pax. Leave all unchecked to apply to none (addition stored but not allocated).</p>
                        <?php $selectedAdditionIds = $voucher->addition_facility_ids ? array_map('intval', explode(',', $voucher->addition_facility_ids)) : []; ?>
                        <div class="row g-2">
                            <?php $__currentLoopData = $facilityTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="addition_facility_ids[]"
                                           value="<?php echo e($facility->id); ?>"
                                           id="addition_facility_<?php echo e($facility->id); ?>"
                                           <?php echo e(in_array($facility->id, $selectedAdditionIds) ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="addition_facility_<?php echo e($facility->id); ?>">
                                        <?php echo e($facility->name); ?>

                                    </label>
                                </div>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php $__errorArgs = ['addition_facility_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                        <a href="<?php echo e(route('vouchers.index')); ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script nonce="<?php echo e($cspNonce); ?>">
    const additionInput = document.querySelector('input[name="addition"]');
    const additionSection = document.getElementById('addition-facility-section');
    if (additionInput && additionSection) {
        additionInput.addEventListener('input', function () {
            additionSection.style.display = parseInt(this.value) > 0 ? 'block' : 'none';
        });
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views/vouchers/edit.blade.php ENDPATH**/ ?>