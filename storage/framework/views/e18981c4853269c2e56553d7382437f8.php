<?php $__env->startSection('title', 'Bookings'); ?>
<?php $__env->startSection('page_title', 'Bookings'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bookings.create')): ?>
    <a href="<?php echo e(route('bookings.create')); ?>" class="btn btn-primary">New Booking</a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Guest</th>
                    <th>Property</th>
                    <th>Stay</th>
                    <th>Pax</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><strong><?php echo e($booking->reference); ?></strong></td>
                    <td><?php echo e($booking->guest->full_name); ?></td>
                    <td><?php echo e($booking->property->name); ?></td>
                    <td><?php echo e($booking->check_in->format('Y-m-d')); ?> – <?php echo e($booking->check_out->format('Y-m-d')); ?></td>
                    <td><?php echo e($booking->total_pax); ?></td>
                    <td><span class="badge bg-<?php echo e($booking->status->value === 'pending' ? 'warning' : 'success'); ?>"><?php echo e($booking->status->value); ?></span></td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="<?php echo e(route('bookings.show', $booking)); ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('bookings.edit', $booking)); ?>" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('bookings.destroy', $booking)); ?>" style="display: inline;" onsubmit="return confirm('Delete this booking?');">
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
                    <td colspan="8" class="text-center py-3 text-muted">No bookings found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($bookings->hasPages()): ?>
    <div class="card-footer"><?php echo e($bookings->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/bookings/index.blade.php ENDPATH**/ ?>