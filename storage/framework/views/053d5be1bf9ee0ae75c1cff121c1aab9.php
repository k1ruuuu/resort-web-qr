
<?php $__env->startSection('title', 'Rooms'); ?>
<?php $__env->startSection('page_title', 'Rooms'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?php echo e(route('rooms.create')); ?>" class="btn btn-primary btn-responsive">
            <i class="fas fa-plus"></i> Add Room
        </a>
        <a href="<?php echo e(route('rooms.import')); ?>" class="btn btn-outline-primary btn-responsive">
            <i class="fas fa-upload"></i> Import
        </a>
    </div>
</div>

    <div class="card">
        <div class="card-body p-0 table-responsive-stack">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Number</th>
                        <th class="table-col-hide-sm">Label</th>
                        <th>Property</th>
                        <th>Type</th>
                        <th class="table-col-hide-xs">Bed Type</th>
                        <th class="table-col-hide-sm">View</th>
                        <th class="table-col-hide-sm">Location</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><strong><?php echo e($room->number); ?></strong></td>
                        <td class="table-col-hide-sm"><?php echo e($room->label ?? '—'); ?></td>
                        <td><?php echo e($room->property->name); ?></td>
                        <td><?php echo e($room->roomType->name); ?></td>
                        <td class="table-col-hide-xs"><span class="badge bg-secondary"><?php echo e($room->bed_type ?? '—'); ?></span></td>
                        <td class="table-col-hide-sm"><?php echo e($room->room_view ?? '—'); ?></td>
                        <td class="table-col-hide-sm"><?php echo e($room->location ? 'Zone ' . $room->location : '—'); ?></td>
                        <td><?php echo e($room->capacity); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($room->status === 'available' ? 'success' : ($room->status === 'occupied' ? 'warning' : 'secondary')); ?>">
                                <?php echo e(ucfirst($room->status)); ?>

                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group-action d-flex gap-1 justify-content-end">
                                <a href="<?php echo e(route('rooms.show', $room)); ?>" class="btn btn-sm btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo e(route('rooms.edit', $room)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('rooms.destroy', $room)); ?>" class="d-inline" onsubmit="return confirm('Delete this room?');">
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
                        <td colspan="10" class="text-center py-3 text-muted">No rooms found.</td>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views/rooms/index.blade.php ENDPATH**/ ?>