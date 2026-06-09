<?php $__env->startSection('title', 'Your Resort Pass'); ?>
<?php $__env->startSection('content'); ?>
<div class="card card-primary card-outline shadow-lg border-0 rounded-4">
    <div class="card-body text-center p-4">
        <div class="mb-3">
            <span class="badge bg-success px-3 py-2 rounded-pill text-uppercase tracking-wider small">Active Stay Pass</span>
        </div>
        
        <h1 class="h3 font-weight-bold text-dark mb-1"><?php echo e($voucher->booking->guest->full_name); ?></h1>
        <p class="text-muted small mb-4">Room: <strong><?php echo e($voucher->booking->room_label ?? $voucher->booking->room?->label ?? 'N/A'); ?></strong></p>

        <div class="p-3 bg-light rounded-4 border mb-4 d-inline-block shadow-inner">
            <?php if (isset($component)) { $__componentOriginal6bb1a263610b0f82be992a1cfba703e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6bb1a263610b0f82be992a1cfba703e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.qr-code','data' => ['url' => $qrImageUrl,'size' => 220,'class' => 'rounded-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('qr-code'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($qrImageUrl),'size' => 220,'class' => 'rounded-3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6bb1a263610b0f82be992a1cfba703e2)): ?>
<?php $attributes = $__attributesOriginal6bb1a263610b0f82be992a1cfba703e2; ?>
<?php unset($__attributesOriginal6bb1a263610b0f82be992a1cfba703e2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6bb1a263610b0f82be992a1cfba703e2)): ?>
<?php $component = $__componentOriginal6bb1a263610b0f82be992a1cfba703e2; ?>
<?php unset($__componentOriginal6bb1a263610b0f82be992a1cfba703e2); ?>
<?php endif; ?>
        </div>

        <div class="row text-left mb-4 bg-light p-3 rounded-3 border g-2">
            <div class="col-6">
                <span class="text-muted d-block small">Stay Dates</span>
                <span class="font-weight-bold text-dark small"><?php echo e($voucher->booking->check_in->format('d M')); ?> – <?php echo e($voucher->booking->check_out->format('d M Y')); ?></span>
            </div>
            <div class="col-6">
                <span class="text-muted d-block small">Total Pax</span>
                <span class="font-weight-bold text-dark small"><?php echo e($voucher->booking->total_pax + $voucher->booking->extra_beds); ?> guests</span>
            </div>
        </div>

        <h3 class="h6 font-weight-bold text-left text-dark border-bottom pb-2 mb-3">
            <i class="fas fa-concierge-bell text-primary me-2"></i> Today's Facility Statuses
        </h3>

        <div class="text-left">
            <?php $__empty_1 = true; $__currentLoopData = $facilityStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="mb-3 p-3 border rounded-3 bg-white shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-weight-bold text-dark"><?php echo e($facility->name); ?></span>
                        <?php if($facility->is_available): ?>
                            <span class="badge bg-info px-2 py-1">Available today</span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-2 py-1">Not available today</span>
                        <?php endif; ?>
                    </div>
                    <?php if($facility->is_available): ?>
                        <div class="progress mb-2" style="height: 8px; border-radius: 4px;">
                            <?php
                                $usedPercent = $facility->quota_total > 0 ? ($facility->quota_used / $facility->quota_total) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo e(100 - $usedPercent); ?>%" aria-valuenow="<?php echo e($facility->quota_remaining); ?>" aria-valuemin="0" aria-valuemax="<?php echo e($facility->quota_total); ?>"></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo e($usedPercent); ?>%" aria-valuenow="<?php echo e($facility->quota_used); ?>" aria-valuemin="0" aria-valuemax="<?php echo e($facility->quota_total); ?>"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Remaining: <strong><?php echo e($facility->quota_remaining); ?></strong></span>
                            <span>Used: <?php echo e($facility->quota_used); ?> / <?php echo e($facility->quota_total); ?></span>
                        </div>
                    <?php else: ?>
                        <p class="mb-0 text-muted small">Period: <?php echo e($facility->start_date->format('d M')); ?> to <?php echo e($facility->end_date->format('d M')); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-muted text-center py-3">No active facilities found for this pass.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/vouchers/public.blade.php ENDPATH**/ ?>