<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">No</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Scan Date/Time</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">QR Code</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Guest Name</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Room</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Outlet</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Scanned By</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Result</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">IP Address</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">User Agent</th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($index + 1); ?></td>
            <td><?php echo e($log->scanned_at->format('Y-m-d H:i:s')); ?></td>
            <td><?php echo e($log->qr_code); ?></td>
            <td><?php echo e($log->guestVoucher?->guest?->full_name ?? 'N/A'); ?></td>
            <td><?php echo e($log->guestVoucher?->booking?->room?->code ?? $log->guestVoucher?->booking?->room?->number ?? 'N/A'); ?></td>
            <td><?php echo e($log->outlet?->name ?? 'N/A'); ?></td>
            <td><?php echo e($log->user?->name ?? 'System'); ?></td>
            <td><?php echo e(strtoupper(str_replace('_', ' ', $log->scan_result))); ?></td>
            <td><?php echo e($log->ip_address); ?></td>
            <td><?php echo e($log->user_agent); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php /**PATH C:\jriw\resort-web-qr\resources\views/exports/scan-history.blade.php ENDPATH**/ ?>