

<?php $__env->startSection('title', 'QR Scan History'); ?>
<?php $__env->startSection('page_title', 'QR Scan History'); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Scan Logs</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo e(route('reports.scan-history')); ?>" class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search QR code or guest name..."
                                   value="<?php echo e(request('search')); ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="scan_result" class="form-control">
                                <option value="">All Results</option>
                                <option value="success" <?php echo e(request('scan_result') === 'success' ? 'selected' : ''); ?>>Success</option>
                                <option value="not_found" <?php echo e(request('scan_result') === 'not_found' ? 'selected' : ''); ?>>Not Found</option>
                                <option value="voucher_not_active" <?php echo e(request('scan_result') === 'voucher_not_active' ? 'selected' : ''); ?>>Voucher Not Active</option>
                                <option value="quota_exceeded" <?php echo e(request('scan_result') === 'quota_exceeded' ? 'selected' : ''); ?>>Quota Exceeded</option>
                                <option value="invalid_date" <?php echo e(request('scan_result') === 'invalid_date' ? 'selected' : ''); ?>>Invalid Date</option>
                                <option value="booking_not_checked_in" <?php echo e(request('scan_result') === 'booking_not_checked_in' ? 'selected' : ''); ?>>Not Checked In</option>
                                <option value="outside_stay_period" <?php echo e(request('scan_result') === 'outside_stay_period' ? 'selected' : ''); ?>>Outside Stay Period</option>
                                <option value="invalid_outlet" <?php echo e(request('scan_result') === 'invalid_outlet' ? 'selected' : ''); ?>>Invalid Outlet</option>
                                <option value="facility_not_linked" <?php echo e(request('scan_result') === 'facility_not_linked' ? 'selected' : ''); ?>>Facility Not Linked</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="outlet_id" class="form-control">
                                <option value="">All Outlets</option>
                                <?php $__currentLoopData = $outlets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outlet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($outlet->id); ?>" <?php echo e(request('outlet_id') == $outlet->id ? 'selected' : ''); ?>>
                                        <?php echo e($outlet->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" 
                                   name="date_from" 
                                   class="form-control" 
                                   placeholder="From Date"
                                   value="<?php echo e(request('date_from')); ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="date" 
                                   name="date_to" 
                                   class="form-control" 
                                   placeholder="To Date"
                                   value="<?php echo e(request('date_to')); ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <?php if(request()->hasAny(['search', 'scan_result', 'outlet_id', 'date_from', 'date_to'])): ?>
                        <div class="mt-2">
                            <a href="<?php echo e(route('reports.scan-history')); ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>QR Code</th>
                                <th>Guest Name</th>
                                <th>Room</th>
                                <th>Outlet</th>
                                <th>Scanned By</th>
                                <th>Result</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <small><?php echo e($log->scanned_at->format('Y-m-d')); ?></small><br>
                                        <small class="text-muted"><?php echo e($log->scanned_at->format('H:i:s')); ?></small>
                                    </td>
                                    <td>
                                        <code class="text-sm"><?php echo e(Str::limit($log->qr_code, 20)); ?></code>
                                    </td>
                                    <td>
                                        <?php if($log->guestVoucher && $log->guestVoucher->guest): ?>
                                            <?php echo e($log->guestVoucher->guest->full_name); ?>

                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($log->guestVoucher && $log->guestVoucher->booking && $log->guestVoucher->booking->room): ?>
                                            <?php echo e($log->guestVoucher->booking->room->label ?? $log->guestVoucher->booking->room->number); ?>

                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($log->outlet->name ?? '-'); ?></td>
                                    <td><?php echo e($log->user->name ?? '-'); ?></td>
                                    <td>
                                        <?php
                                            $badgeClass = match($log->scan_result) {
                                                'success' => 'success',
                                                'not_found' => 'danger',
                                                'voucher_not_active' => 'danger',
                                                'quota_exceeded' => 'warning',
                                                'invalid_date' => 'warning',
                                                'booking_not_checked_in' => 'warning',
                                                'outside_stay_period' => 'warning',
                                                'invalid_outlet' => 'danger',
                                                'facility_not_linked' => 'danger',
                                                default => 'secondary',
                                            };
                                            $displayText = match($log->scan_result) {
                                                'success' => 'Success',
                                                'not_found' => 'Not Found',
                                                'voucher_not_active' => 'Voucher Not Active',
                                                'quota_exceeded' => 'Quota Exceeded',
                                                'invalid_date' => 'Invalid Date',
                                                'booking_not_checked_in' => 'Not Checked In',
                                                'outside_stay_period' => 'Outside Stay Period',
                                                'invalid_outlet' => 'Invalid Outlet',
                                                'facility_not_linked' => 'Facility Not Linked',
                                                default => ucfirst(str_replace('_', ' ', $log->scan_result)),
                                            };
                                        ?>
                                        <span class="badge badge-<?php echo e($badgeClass); ?>"><?php echo e($displayText); ?></span>
                                    </td>
                                    <td><small class="text-muted"><?php echo e($log->ip_address); ?></small></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        <p>No scan logs found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing <?php echo e($logs->firstItem() ?? 0); ?> to <?php echo e($logs->lastItem() ?? 0); ?> of <?php echo e($logs->total()); ?> scans
                    </div>
                    <div>
                        <?php echo e($logs->links()); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/reports/scan-history.blade.php ENDPATH**/ ?>