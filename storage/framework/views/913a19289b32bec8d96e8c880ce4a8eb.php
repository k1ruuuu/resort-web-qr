
<?php $__env->startSection('title', $room->label); ?>
<?php $__env->startSection('page_title', 'Room: ' . $room->label); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Room Details</h3>
                <div class="card-tools">
                    <a href="<?php echo e(route('rooms.edit', $room)); ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="<?php echo e(route('rooms.destroy', $room)); ?>" style="display: inline;" onsubmit="return confirm('Delete this room?');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="fw-bold">Number:</td>
                        <td><?php echo e($room->number); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Label:</td>
                        <td><?php echo e($room->label); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Property:</td>
                        <td>
                            <a href="<?php echo e(route('properties.show', $room->property)); ?>"><?php echo e($room->property->name); ?></a>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Type:</td>
                        <td><?php echo e($room->roomType->name); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Area:</td>
                        <td><?php echo e($room->area?->name ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Floor:</td>
                        <td><?php echo e($room->floor ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Capacity:</td>
                        <td><?php echo e($room->capacity ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Status:</td>
                        <td>
                            <span class="badge bg-<?php echo e($room->is_active ? 'success' : 'secondary'); ?>">
                                <?php echo e($room->is_active ? 'Active' : 'Inactive'); ?>

                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="mt-3">
    <a href="<?php echo e(route('rooms.index')); ?>" class="btn btn-secondary">Back to Rooms</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/rooms/show.blade.php ENDPATH**/ ?>