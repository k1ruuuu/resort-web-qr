<?php $__env->startSection('title', 'Facilities'); ?>
<?php $__env->startSection('page_title', 'Facilities'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3">
    <a href="<?php echo e(route('facilities.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Facility
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Property</th>
                    <th>Sort Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><strong><?php echo e($facility->name); ?></strong></td>
                    <td><code class="text-primary font-weight-bold"><?php echo e($facility->code); ?></code></td>
                    <td><?php echo e($facility->property->name); ?></td>
                    <td><?php echo e($facility->sort_order); ?></td>
                    <td>
                        <?php if($facility->is_active): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="<?php echo e(route('facilities.edit', $facility)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('facilities.destroy', $facility)); ?>" style="display: inline;" onsubmit="return confirm('Delete this facility?');">
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
                    <td colspan="6" class="text-center py-3 text-muted">No facilities found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($facilities->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($facilities->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/facilities/index.blade.php ENDPATH**/ ?>