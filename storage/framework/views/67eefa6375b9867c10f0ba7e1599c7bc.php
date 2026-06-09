<table>
    <tr>
        <td colspan="11" style="text-align: center; font-size: 16px; font-weight: bold; color: #1F4E78;">
            RESORT VOUCHER REDEMPTION REPORT
        </td>
    </tr>
    <tr>
        <td colspan="11" style="text-align: center; font-size: 12px;">
            Generated on: <?php echo e($exportDate); ?>

        </td>
    </tr>
    <tr>
        <td colspan="11" style="text-align: center; font-size: 11px;">
            Total Redemptions: <?php echo e($totalRedemptions); ?> | Total Pax: <?php echo e($totalPax); ?>

        </td>
    </tr>
    <tr><td colspan="11"></td></tr> <!-- Empty row -->
    
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">No</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Date</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Time</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Guest Name</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Room</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Booking Code</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Facility</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Outlet</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Pax Used</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Remaining</th>
            <th style="font-weight: bold; background-color: #2E75B6; color: #FFFFFF;">Staff</th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $redemptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $redemption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($index + 1); ?></td>
            <td><?php echo e($redemption->date->format('Y-m-d')); ?></td>
            <td><?php echo e($redemption->time); ?></td>
            <td><?php echo e($redemption->guest?->full_name ?? 'N/A'); ?></td>
            <td><?php echo e($redemption->booking?->room?->code ?? $redemption->booking?->room?->number ?? 'N/A'); ?></td>
            <td><?php echo e($redemption->booking?->booking_code ?? $redemption->booking?->reference ?? 'N/A'); ?></td>
            <td><?php echo e($redemption->facilityTemplate?->name ?? 'N/A'); ?></td>
            <td><?php echo e($redemption->outlet?->name ?? 'N/A'); ?></td>
            <td style="text-align: center;"><?php echo e($redemption->pax_used); ?></td>
            <td style="text-align: center;"><?php echo e($redemption->remaining_quota); ?></td>
            <td><?php echo e($redemption->user?->name ?? 'System'); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php /**PATH C:\jriw\resort-web-qr\resources\views/exports/redemption-report.blade.php ENDPATH**/ ?>