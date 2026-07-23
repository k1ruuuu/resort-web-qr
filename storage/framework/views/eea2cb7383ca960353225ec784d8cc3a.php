<table>
    <thead>
        <tr>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">No</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Date/Time (Scheduled)</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Guest Name</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Booking Code</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Phone Number</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Message Content</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">QR Code URL</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Status</th>
            <th style="font-weight: bold; background-color: #4472C4; color: #FFFFFF;">Sent At</th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($index + 1); ?></td>
            <td>
                <?php if($log->scheduled_at): ?>
                    <?php echo e($log->scheduled_at->format('Y-m-d H:i:s')); ?>

                <?php else: ?>
                    Immediate
                <?php endif; ?>
            </td>
            <td><?php echo e($log->guest?->full_name ?? 'N/A'); ?></td>
            <td><?php echo e($log->booking?->booking_code ?? $log->booking?->reference ?? 'N/A'); ?></td>
            <td><?php echo e($log->phone_number); ?></td>
            <td><?php echo e($log->message_content); ?></td>
            <td><?php echo e($log->qr_path ?? 'None'); ?></td>
            <td><?php echo e(strtoupper($log->delivery_status)); ?></td>
            <td>
                <?php if($log->sent_at): ?>
                    <?php echo e($log->sent_at->format('Y-m-d H:i:s')); ?>

                <?php else: ?>
                    N/A
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views\exports\delivery-logs.blade.php ENDPATH**/ ?>