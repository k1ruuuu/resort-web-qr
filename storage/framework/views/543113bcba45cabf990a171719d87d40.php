<?php $__env->startSection('title', 'Voucher QR'); ?>
<?php $__env->startSection('page_title', 'Voucher QR'); ?>
<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body text-center">
        <p><?php echo e($voucher->booking->guest->full_name); ?> — <?php echo e($voucher->facilityTemplate->name); ?></p>
        <p class="text-muted"><?php echo e($voucher->valid_date->format('l, d M Y')); ?></p>
        <?php if (isset($component)) { $__componentOriginal6bb1a263610b0f82be992a1cfba703e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6bb1a263610b0f82be992a1cfba703e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.qr-code','data' => ['url' => $qrImageUrl]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('qr-code'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($qrImageUrl)]); ?>
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
        <p class="mt-2 small text-muted">QR: <code><?php echo e($voucher->qr_code); ?></code></p>
        <?php if($voucher->public_token): ?>
        <p><a href="<?php echo e(route('vouchers.public', $voucher->public_token)); ?>" target="_blank">Public link</a></p>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/vouchers/show.blade.php ENDPATH**/ ?>