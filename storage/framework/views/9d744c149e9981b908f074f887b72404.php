<?php $__env->startSection('title', 'Reports'); ?>
<?php $__env->startSection('page_title', 'Reports'); ?>
<?php $__env->startSection('content'); ?>
<form class="row g-2 mb-3" method="GET">
    <div class="col-auto"><input type="date" name="from" value="<?php echo e($from->toDateString()); ?>" class="form-control"></div>
    <div class="col-auto"><input type="date" name="to" value="<?php echo e($to->toDateString()); ?>" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-secondary">Filter</button></div>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reports.export')): ?>
    <div class="col-auto ms-auto">
        <?php if (isset($component)) { $__componentOriginalead669e33878677f706bb89fd3f8e06c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalead669e33878677f706bb89fd3f8e06c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.export-button','data' => ['route' => 'reports.redemptions.export','filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],'text' => 'Export Redemptions']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('export-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'reports.redemptions.export','filters' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['from' => $from->toDateString(), 'to' => $to->toDateString()]),'text' => 'Export Redemptions']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalead669e33878677f706bb89fd3f8e06c)): ?>
<?php $attributes = $__attributesOriginalead669e33878677f706bb89fd3f8e06c; ?>
<?php unset($__attributesOriginalead669e33878677f706bb89fd3f8e06c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalead669e33878677f706bb89fd3f8e06c)): ?>
<?php $component = $__componentOriginalead669e33878677f706bb89fd3f8e06c; ?>
<?php unset($__componentOriginalead669e33878677f706bb89fd3f8e06c); ?>
<?php endif; ?>
    </div>
    <?php endif; ?>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/reports/index.blade.php ENDPATH**/ ?>