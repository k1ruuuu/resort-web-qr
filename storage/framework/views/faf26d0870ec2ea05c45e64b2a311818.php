<?php $__env->startSection('title', 'Booking '.$booking->reference); ?>
<?php $__env->startSection('page_title', 'Booking '.$booking->reference); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Booking Details</h3>
                <div class="card-tools">
                    <a href="<?php echo e(route('bookings.edit', $booking)); ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="<?php echo e(route('bookings.destroy', $booking)); ?>" style="display: inline;" onsubmit="return confirm('Delete this booking?');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Guest:</strong> <?php echo e($booking->guest->full_name); ?></p>
                <p><strong>Property:</strong> <?php echo e($booking->property->name); ?></p>
                <p><strong>Stay:</strong> <?php echo e($booking->check_in->format('Y-m-d')); ?> – <?php echo e($booking->check_out->format('Y-m-d')); ?></p>
                <p><strong>Pax:</strong> <?php echo e($booking->total_pax); ?></p>
                <p><strong>Status:</strong> 
                    <?php
                        $statusBadge = match($booking->status->value) {
                            'checked_in' => 'success',
                            'checked_out' => 'secondary',
                            'confirmed_reservation' => 'info',
                            'cancelled' => 'danger',
                            'pending' => 'warning',
                            default => 'secondary',
                        };
                    ?>
                    <span class="badge badge-<?php echo e($statusBadge); ?>"><?php echo e(ucwords(str_replace('_', ' ', $booking->status->value))); ?></span>
                </p>
                <?php if($booking->checked_in_at): ?>
                    <p><strong>Checked In At:</strong> <?php echo e($booking->checked_in_at->format('Y-m-d H:i:s')); ?></p>
                <?php endif; ?>
                <?php if($booking->checked_out_at): ?>
                    <p><strong>Checked Out At:</strong> <?php echo e($booking->checked_out_at->format('Y-m-d H:i:s')); ?></p>
                <?php endif; ?>
                <?php if($booking->booking_code): ?><p><strong>PMS Code:</strong> <?php echo e($booking->booking_code); ?></p><?php endif; ?>
                <?php if($booking->room_label): ?><p><strong>Room:</strong> <?php echo e($booking->room_label); ?></p><?php endif; ?>
                <p><strong>Quota basis:</strong> total pax (<?php echo e($booking->total_pax); ?>) + <?php echo e($booking->extra_beds); ?> extra bed(s) = <?php echo e($booking->total_pax + $booking->extra_beds); ?> quota</p>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Facilities</div>
            <ul class="list-group list-group-flush">
                <?php $__empty_1 = true; $__currentLoopData = $booking->bookingFacilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="list-group-item">
                        <?php echo e($bf->facilityTemplate->name); ?>

                        <span class="text-muted small">— <?php echo e($bf->start_date->format('Y-m-d')); ?> to <?php echo e($bf->end_date->format('Y-m-d')); ?></span>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="list-group-item text-warning">
                        No facilities linked. Check in again to auto-attach property facilities, or recreate the booking with facilities selected.
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bookings.checkin')): ?>
        <?php if($booking->status->value !== 'checked_in' && $booking->status->value !== 'checked_out'): ?>
        <form method="POST" action="<?php echo e(route('bookings.check-in', $booking)); ?>" class="mb-2">
            <?php echo csrf_field(); ?>
            <button class="btn btn-success w-100">
                <i class="fas fa-sign-in-alt"></i> Check In
            </button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bookings.checkout')): ?>
        <?php if($booking->status->value === 'checked_in'): ?>
        <form method="POST" action="<?php echo e(route('bookings.check-out', $booking)); ?>" class="mb-2" onsubmit="return confirm('Check out this guest? The QR voucher will no longer be usable.');">
            <?php echo csrf_field(); ?>
            <button class="btn btn-danger w-100">
                <i class="fas fa-sign-out-alt"></i> Check Out
            </button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vouchers.generate')): ?>
        <?php if($booking->status->value === 'checked_in' && !$booking->guestVoucher): ?>
        <form method="POST" action="<?php echo e(route('vouchers.generate')); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="booking_id" value="<?php echo e($booking->id); ?>">
            <button class="btn btn-primary w-100">Generate Guest Pass</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vouchers.resend')): ?>
        <?php if($booking->status->value === 'checked_in' && $booking->guestVoucher): ?>
        <form method="POST" action="<?php echo e(route('bookings.resend', $booking)); ?>" class="mt-2">
            <?php echo csrf_field(); ?>
            <button class="btn btn-warning w-100">
                <i class="fab fa-whatsapp"></i> Resend Voucher
            </button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php if($booking->guestVoucher): ?>
<div class="card mt-3">
    <div class="card-header font-weight-bold <?php echo e($booking->guestVoucher->status->value === 'active' ? 'bg-primary text-white' : 'bg-secondary text-white'); ?>">
        Guest Stay Pass 
        <?php
            $voucherBadge = match($booking->guestVoucher->status->value) {
                'active' => 'success',
                'expired' => 'secondary',
                'cancelled' => 'danger',
                'redeemed' => 'info',
                default => 'secondary',
            };
        ?>
        <span class="badge badge-<?php echo e($voucherBadge); ?> float-right"><?php echo e(ucfirst($booking->guestVoucher->status->value)); ?></span>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <?php if (isset($component)) { $__componentOriginal6bb1a263610b0f82be992a1cfba703e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6bb1a263610b0f82be992a1cfba703e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.qr-code','data' => ['url' => route('vouchers.qr', $booking->guestVoucher),'size' => 150,'class' => 'rounded border bg-white p-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('qr-code'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('vouchers.qr', $booking->guestVoucher)),'size' => 150,'class' => 'rounded border bg-white p-1']); ?>
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
            <div class="col-md-8">
                <p class="mb-1"><strong>QR Code Text:</strong> <code class="text-dark"><?php echo e($booking->guestVoucher->qr_code); ?></code></p>
                <p class="mb-1"><strong>Secure Token:</strong> <code class="text-muted"><?php echo e($booking->guestVoucher->secure_token); ?></code></p>
                <p class="mb-1"><strong>Status:</strong> <span class="badge badge-<?php echo e($voucherBadge); ?>"><?php echo e(ucfirst($booking->guestVoucher->status->value)); ?></span></p>
                <p class="mb-1"><strong>Generated At:</strong> <?php echo e($booking->guestVoucher->generated_at?->format('Y-m-d H:i:s')); ?></p>
                <?php if($booking->guestVoucher->status->value !== 'active'): ?>
                    <div class="alert alert-warning mt-2 mb-2">
                        <i class="fas fa-exclamation-triangle"></i> This voucher is no longer active and cannot be used for redemption.
                    </div>
                <?php endif; ?>
                <p class="mb-0 mt-2">
                    <a href="<?php echo e(route('vouchers.show', $booking->guestVoucher)); ?>" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-eye"></i> View Card Details
                    </a>
                    <a href="<?php echo e(route('vouchers.public', $booking->guestVoucher->secure_token)); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-external-link-alt"></i> Open Public Link
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/bookings/show.blade.php ENDPATH**/ ?>