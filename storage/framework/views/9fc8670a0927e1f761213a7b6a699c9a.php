<?php $__env->startSection('title', 'Roles'); ?>
<?php $__env->startSection('page_title', 'Roles'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3">
    <a href="<?php echo e(route('roles.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Role
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Role Name</th>
                    <th>Permissions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><strong><?php echo e($role->name); ?></strong></td>
                    <td>
                        <?php $__empty_2 = true; $__currentLoopData = $role->permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                            <span class="badge bg-secondary mb-1"><?php echo e($permission->name); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                            <span class="text-muted small">No permissions assigned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="<?php echo e(route('roles.edit', $role)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if($role->name !== 'admin'): ?>
                                <form method="POST" action="<?php echo e(route('roles.destroy', $role)); ?>" style="display: inline;" onsubmit="return confirm('Delete this role?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-danger disabled" title="Delete (Core role)" disabled>
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="3" class="text-center py-3 text-muted">No roles found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($roles->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($roles->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/roles/index.blade.php ENDPATH**/ ?>