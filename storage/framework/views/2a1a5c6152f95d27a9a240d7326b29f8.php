<?php $__env->startSection('title', 'Resort Dashboard'); ?>
<?php $__env->startSection('page_title', 'Resort Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<!-- Small boxes (Stat box) -->
<div class="row row-responsive">
    <div class="col-12 col-sm-6 col-lg-3 mb-responsive">
        <div class="small-box bg-info shadow-sm">
            <div class="inner">
                <h3><?php echo e($totalGuests); ?></h3>
                <p>Total Guests</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="<?php echo e(route('guests.index')); ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-responsive">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3><?php echo e($activeGuests); ?></h3>
                <p>Active Guests</p>
            </div>
            <div class="icon">
                <i class="fas fa-hotel"></i>
            </div>
            <a href="<?php echo e(route('bookings.index')); ?>?status=checked_in" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-responsive">
        <div class="small-box bg-warning shadow-sm">
            <div class="inner">
                <h3 class="text-white"><?php echo e($redeemedToday); ?></h3>
                <p class="text-white">Redeemed Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-double text-white-50"></i>
            </div>
            <a href="<?php echo e(route('reports.index')); ?>" class="small-box-footer text-white">More info <i class="fas fa-arrow-circle-right text-white"></i></a>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-responsive">
        <div class="small-box bg-danger shadow-sm">
            <div class="inner">
                <h3><?php echo e($remainingToday); ?></h3>
                <p>Remaining Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="<?php echo e(route('reports.index')); ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- WhatsApp Delivery Stats -->
<h4 class="mt-4 mb-3 text-dark font-weight-bold h4-responsive">
    <i class="fab fa-whatsapp text-success me-2"></i> WhatsApp Delivery
</h4>
<div class="row row-responsive mb-4">
    <div class="col-12 col-sm-6 col-lg-3 mb-responsive">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3><?php echo e($sentToday); ?></h3>
                <p>Sent Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <a href="<?php echo e(route('reports.delivery-logs')); ?>" class="small-box-footer">View Logs <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-responsive">
        <div class="small-box bg-danger shadow-sm">
            <div class="inner">
                <h3><?php echo e($failedDeliveries); ?></h3>
                <p>Failed</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <a href="<?php echo e(route('reports.delivery-logs')); ?>" class="small-box-footer">View Logs <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-responsive">
        <div class="small-box bg-warning shadow-sm">
            <div class="inner">
                <h3 class="text-white"><?php echo e($pendingDeliveries); ?></h3>
                <p class="text-white">Pending</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-half text-white-50"></i>
            </div>
            <a href="<?php echo e(route('reports.delivery-logs')); ?>" class="small-box-footer text-white">View Logs <i class="fas fa-arrow-circle-right text-white"></i></a>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-responsive">
        <div class="small-box bg-primary shadow-sm">
            <div class="inner">
                <h3><?php echo e($successRate); ?>%</h3>
                <p>Success Rate</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <a href="<?php echo e(route('reports.delivery-logs')); ?>" class="small-box-footer">View Logs <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left col: Recent Outlet Activity -->
    <div class="col-12 col-lg-8 mb-responsive">
        <div class="card card-responsive shadow-sm">
            <div class="card-header border-transparent">
                <h3 class="card-title font-weight-bold h5-responsive">
                    <i class="fas fa-history text-muted me-2"></i> Recent Activity
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive overflow-auto-mobile">
                    <table class="table table-striped table-hover m-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Guest</th>
                                <th class="d-none d-md-table-cell">Room</th>
                                <th>Facility</th>
                                <th class="d-none d-lg-table-cell">Pax</th>
                                <th class="d-none d-xl-table-cell">Outlet</th>
                                <th class="d-none d-xl-table-cell">Staff</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $outletActivity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><small><?php echo e($activity->created_at->format('H:i')); ?></small></td>
                                    <td>
                                        <strong class="text-truncate d-inline-block" style="max-width: 120px;" title="<?php echo e($activity->guest->full_name); ?>">
                                            <?php echo e($activity->guest->full_name); ?>

                                        </strong>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-light border text-dark">
                                            <?php echo e($activity->booking->room_label ?? $activity->booking->room?->number ?? 'N/A'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-primary text-truncate d-inline-block" style="max-width: 100px;" title="<?php echo e($activity->facilityTemplate->name); ?>">
                                            <?php echo e($activity->facilityTemplate->name); ?>

                                        </span>
                                    </td>
                                    <td class="d-none d-lg-table-cell"><?php echo e($activity->pax_used); ?></td>
                                    <td class="d-none d-xl-table-cell">
                                        <small><?php echo e($activity->outlet?->name ?? 'N/A'); ?></small>
                                    </td>
                                    <td class="d-none d-xl-table-cell">
                                        <small class="text-muted"><?php echo e($activity->user?->name ?? 'N/A'); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No activity today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right col: Top Used Facilities -->
    <div class="col-12 col-lg-4 mb-responsive">
        <div class="card card-responsive shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold h5-responsive">
                    <i class="fas fa-chart-pie text-muted me-2"></i> Top Facilities
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2">
                    <?php $__empty_1 = true; $__currentLoopData = $topFacilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li class="item py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary rounded-circle me-2 me-md-3 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; flex-shrink: 0;">
                                    <?php echo e($index + 1); ?>

                                </span>
                                <div class="product-info">
                                    <span class="product-title font-weight-bold text-dark text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo e($facility->facility_name); ?>">
                                        <?php echo e($facility->facility_name); ?>

                                    </span>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0 ms-2">
                                <span class="badge bg-info px-2 px-md-3 py-2"><?php echo e($facility->total_pax); ?> Pax</span>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="item text-center py-4 text-muted">No facility redemptions.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/dashboard.blade.php ENDPATH**/ ?>