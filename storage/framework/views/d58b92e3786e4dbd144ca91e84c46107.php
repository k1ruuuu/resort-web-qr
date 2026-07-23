<?php $__env->startSection('title', 'Outlets'); ?>
<?php $__env->startSection('page_title', 'Outlets'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3">
    <a href="<?php echo e(route('outlets.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Outlet
    </a>
</div>

    <div class="card">
        <div class="card-body p-0 table-responsive-stack">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Property</th>
                        <th class="table-col-hide-sm">Facilities</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $outlets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outlet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><strong><?php echo e($outlet->name); ?></strong></td>
                        <td><code class="text-primary font-weight-bold"><?php echo e($outlet->code); ?></code></td>
                        <td><?php echo e($outlet->property->name); ?></td>
                        <td class="table-col-hide-sm">
                            <?php $__empty_2 = true; $__currentLoopData = $outlet->facilityTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ft): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                <span class="badge bg-info me-1"><?php echo e($ft->name); ?></span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($outlet->is_active): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group-action d-flex gap-1">
                                <a href="<?php echo e(route('outlets.edit', $outlet)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('outlets.destroy', $outlet)); ?>" style="display: inline;" onsubmit="return confirm('Delete this outlet?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted">No outlets found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php if($outlets->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($outlets->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views\outlets\index.blade.php ENDPATH**/ ?>