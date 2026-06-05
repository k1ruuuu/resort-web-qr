<?php $__env->startSection('title', 'Resort Dashboard'); ?>
<?php $__env->startSection('page_title', 'Resort Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<!-- Small boxes (Stat box) -->
<div class="row">
    <div class="col-lg-3 col-6">
        <!-- small box -->
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
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3><?php echo e($activeGuests); ?></h3>
                <p>Active Guests (In-House)</p>
            </div>
            <div class="icon">
                <i class="fas fa-hotel"></i>
            </div>
            <a href="<?php echo e(route('bookings.index')); ?>?status=checked_in" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-warning shadow-sm">
            <div class="inner">
                <h3 class="text-white"><?php echo e($redeemedToday); ?></h3>
                <p class="text-white">Redeemed Facilities Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-double text-white-50"></i>
            </div>
            <a href="<?php echo e(route('reports.index')); ?>" class="small-box-footer text-white">More info <i class="fas fa-arrow-circle-right text-white"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-danger shadow-sm">
            <div class="inner">
                <h3><?php echo e($remainingToday); ?></h3>
                <p>Remaining Facilities Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="<?php echo e(route('reports.index')); ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
</div>

<!-- WhatsApp Delivery Stats -->
<h4 class="mt-4 mb-3 text-dark font-weight-bold"><i class="fab fa-whatsapp text-success me-2"></i> WhatsApp Delivery Summary</h4>
<div class="row mb-4">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3><?php echo e($sentToday); ?></h3>
                <p>Vouchers Sent Today</p>
            </div>
            <div class="icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <a href="<?php echo e(route('reports.delivery-logs')); ?>" class="small-box-footer">View Logs <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger shadow-sm">
            <div class="inner">
                <h3><?php echo e($failedDeliveries); ?></h3>
                <p>Failed Deliveries</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <a href="<?php echo e(route('reports.delivery-logs')); ?>" class="small-box-footer">View Logs <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning shadow-sm">
            <div class="inner">
                <h3 class="text-white"><?php echo e($pendingDeliveries); ?></h3>
                <p class="text-white">Pending Deliveries</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-half text-white-50"></i>
            </div>
            <a href="<?php echo e(route('reports.delivery-logs')); ?>" class="small-box-footer text-white">View Logs <i class="fas fa-arrow-circle-right text-white"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary shadow-sm">
            <div class="inner">
                <h3><?php echo e($successRate); ?>%</h3>
                <p>Delivery Success Rate</p>
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
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header border-transparent">
                <h3 class="card-title font-weight-bold"><i class="fas fa-history text-muted me-2"></i> Recent Outlet Activity</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Facility</th>
                                <th>Pax</th>
                                <th>Outlet</th>
                                <th>Staff</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $outletActivity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($activity->created_at->diffForHumans()); ?></td>
                                    <td><strong><?php echo e($activity->guest->full_name); ?></strong></td>
                                    <td><span class="badge bg-light border text-dark"><?php echo e($activity->booking->room_label ?? $activity->booking->room?->number ?? 'N/A'); ?></span></td>
                                    <td><span class="text-primary font-weight-bold"><?php echo e($activity->facilityTemplate->name); ?></span></td>
                                    <td><?php echo e($activity->pax_used); ?></td>
                                    <td><?php echo e($activity->outlet?->name ?? 'N/A'); ?></td>
                                    <td><small class="text-muted"><?php echo e($activity->user?->name ?? 'N/A'); ?></small></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No activity logged today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- /.table-responsive -->
            </div>
            <!-- /.card-body -->
        </div>
    </div>

    <!-- Right col: Top Used Facilities -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-chart-pie text-muted me-2"></i> Top Used Facilities</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2">
                    <?php $__empty_1 = true; $__currentLoopData = $topFacilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li class="item py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <?php echo e($index + 1); ?>

                                </span>
                                <div class="product-info">
                                    <span class="product-title font-weight-bold text-dark"><?php echo e($facility->facility_name); ?></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge bg-info px-3 py-2"><?php echo e($facility->total_pax); ?> Pax</span>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="item text-center py-4 text-muted">No facility redemptions recorded.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- /.card-body -->
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/dashboard.blade.php ENDPATH**/ ?>