<?php $__env->startSection('title', 'Vouchers'); ?>
<?php $__env->startSection('page_title', 'Vouchers'); ?>
<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Guest</th>
                    <th>Facility</th>
                    <th>Quota</th>
                    <th>QR Code</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $vouchers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($voucher->valid_date->format('Y-m-d')); ?></td>
                    <td><?php echo e($voucher->booking->guest->full_name); ?></td>
                    <td><?php echo e($voucher->facilityTemplate->name); ?></td>
                    <td><?php echo e($voucher->quota_remaining); ?>/<?php echo e($voucher->quota_total); ?></td>
                    <td><small><?php echo e($voucher->qr_code); ?></small></td>
                    <td><?php echo e($voucher->status->value); ?></td>
                    <td><a href="<?php echo e(route('vouchers.show', $voucher)); ?>" class="btn btn-sm btn-outline-primary">View QR</a></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php if($vouchers->hasPages()): ?><div class="card-footer"><?php echo e($vouchers->links()); ?></div><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/vouchers/index.blade.php ENDPATH**/ ?>