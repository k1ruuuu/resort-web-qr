<?php $__env->startSection('title', 'Booking '.$booking->reference); ?>
<?php $__env->startSection('page_title', 'Booking '.$booking->reference); ?>
<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Booking Details</h3>
                <div class="card-tools">
                    <a href="<?php echo e(route('bookings.edit', $booking)); ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="<?php echo e(route('bookings.destroy', $booking)); ?>" style="display: inline;" onsubmit="return confirm('Delete this booking?');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Guest:</strong> <?php echo e($booking->guest->full_name); ?></p>
                <p><strong>Property:</strong> <?php echo e($booking->property->name); ?></p>
                <p><strong>Stay:</strong> <?php echo e($booking->check_in->format('Y-m-d')); ?> – <?php echo e($booking->check_out->format('Y-m-d')); ?></p>
                <p><strong>Pax:</strong> <?php echo e($booking->total_pax); ?></p>
                <p><strong>Status:</strong> <?php echo e($booking->status->value); ?></p>
                <?php if($booking->booking_code): ?><p><strong>PMS Code:</strong> <?php echo e($booking->booking_code); ?></p><?php endif; ?>
                <?php if($booking->room_label): ?><p><strong>Room:</strong> <?php echo e($booking->room_label); ?></p><?php endif; ?>
                <p><strong>Quota basis:</strong> room capacity + <?php echo e($booking->extra_beds); ?> extra bed(s)</p>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Facilities</div>
            <ul class="list-group list-group-flush">
                <?php $__empty_1 = true; $__currentLoopData = $booking->bookingFacilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="list-group-item">
                        <?php echo e($bf->facilityTemplate->name); ?> (quota <?php echo e($bf->quota_total); ?>)
                        <span class="text-muted small">— <?php echo e($bf->start_date->format('Y-m-d')); ?> to <?php echo e($bf->end_date->format('Y-m-d')); ?></span>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="list-group-item text-warning">
                        No facilities linked. Check in again to auto-attach property facilities, or recreate the booking with facilities selected.
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bookings.checkin')): ?>
        <?php if($booking->status->value !== 'checked_in'): ?>
        <form method="POST" action="<?php echo e(route('bookings.check-in', $booking)); ?>" class="mb-2">
            <?php echo csrf_field(); ?>
            <button class="btn btn-success w-100">Check In</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vouchers.generate')): ?>
        <?php if($booking->status->value === 'checked_in'): ?>
        <form method="POST" action="<?php echo e(route('vouchers.generate')); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="booking_id" value="<?php echo e($booking->id); ?>">
            <button class="btn btn-primary w-100">Generate Today Vouchers</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php if($booking->dailyVouchers->isNotEmpty()): ?>
<div class="card mt-3">
    <div class="card-header">Vouchers</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Date</th><th>Facility</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $booking->dailyVouchers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($voucher->valid_date->format('Y-m-d')); ?></td>
                    <td><?php echo e($voucher->facilityTemplate->name); ?></td>
                    <td><?php echo e($voucher->status->value); ?></td>
                    <td><a href="<?php echo e(route('vouchers.show', $voucher)); ?>">QR</a></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/bookings/show.blade.php ENDPATH**/ ?>