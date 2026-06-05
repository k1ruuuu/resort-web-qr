<?php $__env->startSection('title', 'Reports'); ?>
<?php $__env->startSection('page_title', 'Reports'); ?>
<?php $__env->startSection('content'); ?>
<form class="row g-2 mb-3" method="GET">
    <div class="col-auto"><input type="date" name="from" value="<?php echo e($from->toDateString()); ?>" class="form-control"></div>
    <div class="col-auto"><input type="date" name="to" value="<?php echo e($to->toDateString()); ?>" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-secondary">Filter</button></div>
</form>
<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Redemptions by Facility</div>
            <table class="table mb-0">
                <thead><tr><th>Facility</th><th>Events</th><th>Pax</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $redemptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($row->facility_name); ?></td>
                        <td><?php echo e($row->redemption_count); ?></td>
                        <td><?php echo e($row->total_pax); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="3" class="text-muted">No data in range.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Voucher Status</div>
            <table class="table mb-0">
                <?php $__currentLoopData = $voucherStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr><td><?php echo e($row->status); ?></td><td><?php echo e($row->total); ?></td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/reports/index.blade.php ENDPATH**/ ?>