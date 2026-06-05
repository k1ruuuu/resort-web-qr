<?php $__env->startSection('title', 'Guests'); ?>
<?php $__env->startSection('page_title', 'Guests'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3">
    <a href="<?php echo e(route('guests.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Guest
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Document ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $guests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><strong><?php echo e($guest->full_name); ?></strong></td>
                    <td><?php echo e($guest->email ?? '—'); ?></td>
                    <td><?php echo e($guest->phone ?? '—'); ?></td>
                    <td><small><?php echo e($guest->document_id ?? '—'); ?></small></td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="<?php echo e(route('guests.show', $guest)); ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('guests.edit', $guest)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('guests.destroy', $guest)); ?>" style="display: inline;" onsubmit="return confirm('Delete this guest?');">
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
                    <td colspan="5" class="text-center py-3 text-muted">No guests found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($guests->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($guests->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/guests/index.blade.php ENDPATH**/ ?>