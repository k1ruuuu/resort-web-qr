<?php $__env->startSection('title', 'Guests'); ?>
<?php $__env->startSection('page_title', 'Guests'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    <a href="<?php echo e(route('guests.create')); ?>" class="btn btn-primary btn-responsive">
        <i class="fas fa-plus"></i> Add Guest
    </a>
    
    <form method="GET" action="<?php echo e(route('guests.index')); ?>" class="d-flex gap-2 w-100" style="max-width: 400px;">
        <input type="text" 
               name="search" 
               class="form-control" 
               placeholder="Search guests..."
               value="<?php echo e(request('search')); ?>">
        <button type="submit" class="btn btn-primary flex-shrink-0">
            <i class="fas fa-search"></i>
        </button>
        <?php if(request()->filled('search')): ?>
        <a href="<?php echo e(route('guests.index')); ?>" class="btn btn-secondary flex-shrink-0" title="Clear">
            <i class="fas fa-times"></i>
        </a>
        <?php endif; ?>
    </form>
</div>

<div class="card card-responsive">
    <div class="card-body p-0">
        <div class="table-responsive overflow-auto-mobile">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="d-none d-md-table-cell">Email</th>
                        <th class="d-none d-lg-table-cell">Phone</th>
                        <th class="d-none d-xl-table-cell">Document ID</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $guests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <strong><?php echo e($guest->full_name); ?></strong>
                            <div class="d-md-none">
                                <small class="text-muted">
                                    <?php echo e($guest->email ?? $guest->phone ?? '—'); ?>

                                </small>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo e($guest->email); ?>">
                                <?php echo e($guest->email ?? '—'); ?>

                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell"><?php echo e($guest->phone ?? '—'); ?></td>
                        <td class="d-none d-xl-table-cell">
                            <small><?php echo e($guest->document_id ?? '—'); ?></small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?php echo e(route('guests.show', $guest)); ?>" class="btn btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo e(route('guests.edit', $guest)); ?>" class="btn btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('guests.destroy', $guest)); ?>" class="d-inline" onsubmit="return confirm('Delete this guest?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <?php if(request()->filled('search')): ?>
                                No guests found matching "<?php echo e(request('search')); ?>".
                            <?php else: ?>
                                No guests found.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($guests->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($guests->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/guests/index.blade.php ENDPATH**/ ?>