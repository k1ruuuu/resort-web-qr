<?php $__env->startSection('title', 'WhatsApp Delivery Logs'); ?>
<?php $__env->startSection('page_title', 'WhatsApp Delivery Logs'); ?>
<?php $__env->startSection('content'); ?>
<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold mb-0">
            <i class="fas fa-list text-muted me-2"></i> WhatsApp Message Logs
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Guest</th>
                        <th>Phone</th>
                        <th>Message Content</th>
                        <th>QR Code URL</th>
                        <th>Status</th>
                        <th>Sent At</th>
                        <th>API Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <?php if($log->scheduled_at): ?>
                                    <span class="text-muted d-block small">Scheduled:</span>
                                    <span><?php echo e($log->scheduled_at->format('Y-m-d H:i')); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">Immediate</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo e($log->guest->full_name); ?></strong>
                                <span class="d-block small text-muted">Booking: <?php echo e($log->booking->booking_code ?? $log->booking->reference); ?></span>
                            </td>
                            <td><code class="text-dark"><?php echo e($log->phone_number); ?></code></td>
                            <td>
                                <a href="#" class="small" data-bs-toggle="collapse" data-bs-target="#msg-<?php echo e($log->id); ?>">
                                    View Message (<?php echo e(strlen($log->message_content)); ?> chars)
                                </a>
                                <div id="msg-<?php echo e($log->id); ?>" class="collapse mt-1 p-2 bg-light border rounded small" style="white-space: pre-wrap;"><?php echo e($log->message_content); ?></div>
                            </td>
                            <td>
                                <?php if($log->qr_path): ?>
                                    <a href="<?php echo e($log->qr_path); ?>" target="_blank" class="small text-truncate d-inline-block" style="max-width: 150px;">
                                        <?php echo e(basename($log->qr_path)); ?>

                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo e($log->delivery_status === 'sent' ? 'success' : ($log->delivery_status === 'failed' ? 'danger' : 'warning')); ?> px-2 py-1">
                                    <?php echo e($log->delivery_status); ?>

                                </span>
                            </td>
                            <td>
                                <?php if($log->sent_at): ?>
                                    <span><?php echo e($log->sent_at->format('Y-m-d H:i:s')); ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($log->provider_response): ?>
                                    <a href="#" class="small text-monospace" data-bs-toggle="collapse" data-bs-target="#resp-<?php echo e($log->id); ?>">
                                        Show Raw
                                    </a>
                                    <div id="resp-<?php echo e($log->id); ?>" class="collapse mt-1 p-2 bg-dark text-light border rounded small font-monospace" style="max-width: 250px; overflow-x: auto;">
                                        <?php echo e($log->provider_response); ?>

                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No delivery logs recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($logs->hasPages()): ?>
        <div class="card-footer"><?php echo e($logs->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/reports/delivery_logs.blade.php ENDPATH**/ ?>