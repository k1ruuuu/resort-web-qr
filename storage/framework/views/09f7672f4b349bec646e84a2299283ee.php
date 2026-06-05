
<?php $__env->startSection('title', $guest->full_name); ?>
<?php $__env->startSection('page_title', 'Guest: ' . $guest->full_name); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Guest Details</h3>
                <div class="card-tools">
                    <a href="<?php echo e(route('guests.edit', $guest)); ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="<?php echo e(route('guests.destroy', $guest)); ?>" style="display: inline;" onsubmit="return confirm('Delete this guest?');">
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
                        <td class="fw-bold">First Name:</td>
                        <td><?php echo e($guest->first_name); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Last Name:</td>
                        <td><?php echo e($guest->last_name); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Email:</td>
                        <td>
                            <?php if($guest->email): ?>
                                <a href="mailto:<?php echo e($guest->email); ?>"><?php echo e($guest->email); ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Phone:</td>
                        <td>
                            <?php if($guest->phone): ?>
                                <a href="tel:<?php echo e($guest->phone); ?>"><?php echo e($guest->phone); ?></a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">WhatsApp:</td>
                        <td><?php echo e($guest->whatsapp ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Document ID:</td>
                        <td><?php echo e($guest->document_id ?? '—'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <?php if($guest->bookings->count() > 0): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Bookings (<?php echo e($guest->bookings->count()); ?>)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Property</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $guest->bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td>
                                <a href="<?php echo e(route('bookings.show', $booking)); ?>"><?php echo e($booking->booking_code); ?></a>
                            </td>
                            <td><?php echo e($booking->property->name); ?></td>
                            <td><?php echo e($booking->check_in->format('M d, Y')); ?></td>
                            <td><?php echo e($booking->check_out->format('M d, Y')); ?></td>
                            <td><?php echo e($booking->status->value); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<div class="mt-3">
    <a href="<?php echo e(route('guests.index')); ?>" class="btn btn-secondary">Back to Guests</a>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/guests/show.blade.php ENDPATH**/ ?>