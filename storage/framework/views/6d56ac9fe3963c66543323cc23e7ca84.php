<?php $__env->startSection('title', 'Properties'); ?>
<?php $__env->startSection('page_title', 'Properties'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3">
    <a href="<?php echo e(route('properties.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Property
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Timezone</th>
                    <th>Rooms</th>
                    <th>Bookings</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><strong><?php echo e($property->name); ?></strong></td>
                    <td><code><?php echo e($property->code); ?></code></td>
                    <td><?php echo e($property->timezone); ?></td>
                    <td><?php echo e($property->rooms_count); ?></td>
                    <td><?php echo e($property->bookings_count); ?></td>
                    <td>
                        <span class="badge bg-<?php echo e($property->is_active ? 'success' : 'secondary'); ?>">
                            <?php echo e($property->is_active ? 'Active' : 'Inactive'); ?>

                        </span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="<?php echo e(route('properties.show', $property)); ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('properties.edit', $property)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('properties.destroy', $property)); ?>" style="display: inline;" onsubmit="return confirm('Delete this property?');">
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
                    <td colspan="7" class="text-center py-3 text-muted">No properties found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($properties->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($properties->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/properties/index.blade.php ENDPATH**/ ?>