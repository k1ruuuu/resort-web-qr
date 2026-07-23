
<?php $__env->startSection('title', $property->name); ?>
<?php $__env->startSection('page_title', $property->name); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Property Details</h3>
                <div class="card-tools">
                    <a href="<?php echo e(route('properties.edit', $property)); ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="<?php echo e(route('properties.destroy', $property)); ?>" style="display: inline;" onsubmit="return confirm('Delete this property?');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="detail-table-responsive">
                <table class="table table-sm">
                    <tr>
                        <td class="fw-bold">Name:</td>
                        <td><?php echo e($property->name); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Code:</td>
                        <td><code><?php echo e($property->code); ?></code></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Timezone:</td>
                        <td><?php echo e($property->timezone); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Address:</td>
                        <td><?php echo e($property->address ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Status:</td>
                        <td>
                            <span class="badge bg-<?php echo e($property->is_active ? 'success' : 'secondary'); ?>">
                                <?php echo e($property->is_active ? 'Active' : 'Inactive'); ?>

                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Rooms:</td>
                        <td><?php echo e($property->rooms_count); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Bookings:</td>
                        <td><?php echo e($property->bookings_count); ?></td>
                    </tr>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="mt-3">
    <a href="<?php echo e(route('properties.index')); ?>" class="btn btn-secondary">Back to Properties</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views\properties\show.blade.php ENDPATH**/ ?>