
<?php $__env->startSection('title', 'Digital Guest Voucher'); ?>
<?php $__env->startSection('page_title', 'Digital Guest Voucher'); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card card-primary card-outline shadow-lg">
            <div class="card-body box-profile text-center">
                <div class="text-center mb-3">
                    <i class="fas fa-id-card fa-3x text-primary"></i>
                </div>
                <h3 class="profile-username text-center font-weight-bold"><?php echo e($voucher->guest_name ?? ($voucher->booking?->guest?->full_name ?? 'Temporary Guest')); ?></h3>
                <p class="text-muted text-center mb-4">Digital Guest Card</p>

                <div class="p-3 bg-light rounded border mb-4">
                    <?php if (isset($component)) { $__componentOriginal6bb1a263610b0f82be992a1cfba703e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6bb1a263610b0f82be992a1cfba703e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.qr-code','data' => ['url' => $qrImageUrl,'size' => 240,'class' => 'rounded shadow-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('qr-code'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($qrImageUrl),'size' => 240,'class' => 'rounded shadow-sm']); ?>
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
                    <p class="mt-3 mb-0 small text-muted text-monospace">
                        QR Code: <code><?php echo e(substr($voucher->qr_code, 0, 12)); ?><?php echo e(strlen($voucher->qr_code) > 12 ? '...' : ''); ?></code>
                    </p>
                </div>

                <ul class="list-group list-group-unbordered mb-4 text-left">
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Room</b> 
                        <span><?php echo e($voucher->booking?->room_label ?? $voucher->booking?->room?->label ?? 'Temporary'); ?> (<?php echo e($voucher->booking?->room?->code ?? 'TEMP'); ?>)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Stay Dates</b> 
                        <span><?php echo e($voucher->booking ? $voucher->booking->check_in->format('d M Y') . ' – ' . $voucher->booking->check_out->format('d M Y') : ($voucher->expires_at ? $voucher->expires_at->format('d M Y H:i') : 'N/A')); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Total Pax</b> 
                        <span>
                            <?php if($voucher->booking): ?>
                                <?php echo e($voucher->booking->total_pax); ?> (+ <?php echo e($voucher->booking->extra_beds); ?> Extra Bed)<?php echo e($voucher->addition ? ' (+' . $voucher->addition . ' Addition)' : ''); ?> = <strong><?php echo e($voucher->booking->total_pax + $voucher->booking->extra_beds + ($voucher->addition ?? 0)); ?> Quota</strong>
                            <?php else: ?>
                                <?php echo e(($voucher->pax_limit ?? 1) + ($voucher->addition ?? 0)); ?>

                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Booking Ref</b> 
                        <span><?php echo e($voucher->booking?->booking_code ?? $voucher->booking?->reference ?? 'Temporary'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <b>Status</b> 
                        <span class="badge bg-<?php echo e($voucher->status->value === 'active' ? 'success' : 'secondary'); ?> d-flex align-items-center px-2"><?php echo e($voucher->status->value); ?></span>
                    </li>
                </ul>

                <a href="<?php echo e(route('vouchers.public', $voucher->secure_token)); ?>" target="_blank" class="btn btn-primary btn-block">
                    <i class="fas fa-external-link-alt"></i> View Public Guest Card
                </a>
                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vouchers.resend')): ?>
                <?php if($voucher->booking_id): ?>
                <form method="POST" action="<?php echo e(route('bookings.resend', $voucher->booking_id)); ?>" class="mt-2">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-warning btn-block">
                        <i class="fab fa-whatsapp"></i> Resend Voucher via WhatsApp
                    </button>
                </form>
                <?php endif; ?>
                <?php endif; ?>

                
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views/vouchers/show.blade.php ENDPATH**/ ?>