<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page_title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo e($bookingCount); ?></h3>
                <p>Total Bookings</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo e($activeVouchers); ?></h3>
                <p>Active Vouchers</p>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">Voucher Status</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Status</th><th>Count</th></tr></thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $voucherStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr><td><?php echo e($row->status); ?></td><td><?php echo e($row->total); ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="2" class="text-muted">No vouchers yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/dashboard.blade.php ENDPATH**/ ?>