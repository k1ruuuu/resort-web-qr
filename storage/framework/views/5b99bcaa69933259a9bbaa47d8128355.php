<?php $__env->startSection('title', 'Guest Vouchers'); ?>
<?php $__env->startSection('page_title', 'Guest Vouchers'); ?>
<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Stay Dates</th>
                    <th>QR Code Text</th>
                    <th>Secure Token</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $vouchers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><strong><?php echo e($voucher->booking->guest->full_name); ?></strong></td>
                    <td><?php echo e($voucher->booking->room_label ?? $voucher->booking->room?->label ?? 'N/A'); ?></td>
                    <td><?php echo e($voucher->booking->check_in->format('Y-m-d')); ?> – <?php echo e($voucher->booking->check_out->format('Y-m-d')); ?></td>
                    <td><small class="text-mono"><?php echo e($voucher->qr_code); ?></small></td>
                    <td><small class="text-mono text-muted"><?php echo e(substr($voucher->secure_token, 0, 8)); ?>...</small></td>
                    <td>
                        <span class="badge bg-<?php echo e($voucher->status->value === 'active' ? 'success' : 'secondary'); ?>">
                            <?php echo e($voucher->status->value); ?>

                        </span>
                    </td>
                    <td>
                        <a href="<?php echo e(route('vouchers.show', $voucher)); ?>" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-qrcode"></i> View QR Card
                        </a>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php if($vouchers->hasPages()): ?><div class="card-footer"><?php echo e($vouchers->links()); ?></div><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/vouchers/index.blade.php ENDPATH**/ ?>