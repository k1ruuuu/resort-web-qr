<?php $__env->startSection('title', 'Rooms'); ?>
<?php $__env->startSection('page_title', 'Rooms'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3">
    <a href="<?php echo e(route('rooms.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Room
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Label</th>
                    <th>Code</th>
                    <th>Property</th>
                    <th>Type</th>
                    <th>Area</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><strong><?php echo e($room->number); ?></strong></td>
                    <td><?php echo e($room->label ?? '—'); ?></td>
                    <td><code><?php echo e($room->code ?? '—'); ?></code></td>
                    <td><?php echo e($room->property->name); ?></td>
                    <td><?php echo e($room->roomType->name); ?></td>
                    <td><?php echo e($room->area?->name ?? '—'); ?></td>
                    <td><?php echo e($room->capacity); ?></td>
                    <td>
                        <span class="badge bg-<?php echo e($room->status === 'available' ? 'success' : ($room->status === 'occupied' ? 'warning' : 'secondary')); ?>">
                            <?php echo e(ucfirst($room->status)); ?>

                        </span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="<?php echo e(route('rooms.show', $room)); ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('rooms.edit', $room)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('rooms.destroy', $room)); ?>" style="display: inline;" onsubmit="return confirm('Delete this room?');">
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
                    <td colspan="9" class="text-center py-3 text-muted">No rooms found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($rooms->hasPages()): ?>
        <div class="card-footer">
            <?php echo e($rooms->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/rooms/index.blade.php ENDPATH**/ ?>