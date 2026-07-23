
<?php $__env->startSection('title', 'Reports'); ?>
<?php $__env->startSection('page_title', 'Reports'); ?>
<?php $__env->startSection('content'); ?>


<div class="card card-outline card-primary mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Report Filters</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-tool d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#reportFilterCollapse" aria-expanded="true">
                <i class="fas fa-chevron-down"></i>
            </button>
            <span class="badge bg-primary"><?php echo e($periodLabel); ?></span>
        </div>
    </div>
    <div class="collapse collapse-md-show" id="reportFilterCollapse">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('reports.index')); ?>" id="report-filter-form">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Filter By</label>
                    <select name="filter_type" id="filter_type" class="form-select">
                        <option value="date_range" <?php if($filterType === 'date_range'): echo 'selected'; endif; ?>>Date Range</option>
                        <option value="month" <?php if($filterType === 'month'): echo 'selected'; endif; ?>>Month</option>
                        <option value="year" <?php if($filterType === 'year'): echo 'selected'; endif; ?>>Year</option>
                    </select>
                </div>

                <div class="col-md-3 filter-group" id="filter-date-range" <?php if($filterType !== 'date_range'): ?> style="display:none" <?php endif; ?>>
                    <label class="form-label fw-semibold">From</label>
                    <input type="date" name="from" value="<?php echo e($from->toDateString()); ?>" class="form-control">
                </div>
                <div class="col-md-3 filter-group" id="filter-date-range-to" <?php if($filterType !== 'date_range'): ?> style="display:none" <?php endif; ?>>
                    <label class="form-label fw-semibold">To</label>
                    <input type="date" name="to" value="<?php echo e($to->toDateString()); ?>" class="form-control">
                </div>

                <div class="col-md-3 filter-group" id="filter-month" <?php if($filterType !== 'month'): ?> style="display:none" <?php endif; ?>>
                    <label class="form-label fw-semibold">Month</label>
                    <select name="month" class="form-select">
                        <?php $__currentLoopData = range(1, 12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($m); ?>" <?php if($month == $m): echo 'selected'; endif; ?>><?php echo e(\Carbon\Carbon::create(null, $m, 1)->format('F')); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3 filter-group" id="filter-month-year" <?php if($filterType !== 'month'): ?> style="display:none" <?php endif; ?>>
                    <label class="form-label fw-semibold">Year</label>
                    <select name="year" class="form-select">
                        <?php $__currentLoopData = range(now()->year, now()->year - 5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($y); ?>" <?php if($year == $y): echo 'selected'; endif; ?>><?php echo e($y); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-md-3 filter-group" id="filter-year-only" <?php if($filterType !== 'year'): ?> style="display:none" <?php endif; ?>>
                    <label class="form-label fw-semibold">Year</label>
                    <select name="year" class="form-select">
                        <?php $__currentLoopData = range(now()->year, now()->year - 5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($y); ?>" <?php if($year == $y): echo 'selected'; endif; ?>><?php echo e($y); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Property</label>
                    <select name="property_id" class="form-select">
                        <option value="">All Properties</option>
                        <?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($property->id); ?>" <?php if($propertyId == $property->id): echo 'selected'; endif; ?>><?php echo e($property->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Facility</label>
                    <select name="facility_id" class="form-select">
                        <option value="">All Facilities</option>
                        <?php $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($facility->id); ?>" <?php if($facilityId == $facility->id): echo 'selected'; endif; ?>><?php echo e($facility->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Outlet</label>
                    <select name="outlet_id" class="form-select">
                        <option value="">All Outlets</option>
                        <?php $__currentLoopData = $outlets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outlet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($outlet->id); ?>" <?php if($outletId == $outlet->id): echo 'selected'; endif; ?>><?php echo e($outlet->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search"></i> Apply
                    </button>
                    <a href="<?php echo e(route('reports.index')); ?>" class="btn btn-secondary" title="Reset">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reports.export')): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Export includes summary, breakdowns, and full redemption log for the selected period.</small>
        <?php if (isset($component)) { $__componentOriginalead669e33878677f706bb89fd3f8e06c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalead669e33878677f706bb89fd3f8e06c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.export-button','data' => ['route' => 'reports.redemptions.export','filters' => request()->only(['filter_type', 'from', 'to', 'month', 'year', 'property_id', 'facility_id', 'outlet_id']),'text' => 'Export Report']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('export-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['route' => 'reports.redemptions.export','filters' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->only(['filter_type', 'from', 'to', 'month', 'year', 'property_id', 'facility_id', 'outlet_id'])),'text' => 'Export Report']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalead669e33878677f706bb89fd3f8e06c)): ?>
<?php $attributes = $__attributesOriginalead669e33878677f706bb89fd3f8e06c; ?>
<?php unset($__attributesOriginalead669e33878677f706bb89fd3f8e06c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalead669e33878677f706bb89fd3f8e06c)): ?>
<?php $component = $__componentOriginalead669e33878677f706bb89fd3f8e06c; ?>
<?php unset($__componentOriginalead669e33878677f706bb89fd3f8e06c); ?>
<?php endif; ?>
    </div>
    <?php endif; ?>
    </div>
</div>


<div class="row mb-4">
    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-info"><i class="fas fa-receipt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Redemptions</span>
                <span class="info-box-number"><?php echo e(number_format($overview->total_redemptions)); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-success"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Pax Redeemed</span>
                <span class="info-box-number"><?php echo e(number_format($overview->total_pax)); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-warning"><i class="fas fa-user-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Unique Guests</span>
                <span class="info-box-number"><?php echo e(number_format($overview->unique_guests)); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-primary"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Avg Pax / Redemption</span>
                <span class="info-box-number"><?php echo e($overview->avg_pax_per_redemption); ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-secondary h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building mr-1"></i> Redemptions by Facility</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Facility</th>
                                <th class="text-end">Events</th>
                                <th class="text-end">Pax</th>
                                <th class="text-end" style="width:120px">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $redemptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php $share = $overview->total_pax > 0 ? round(($row->total_pax / $overview->total_pax) * 100, 1) : 0; ?>
                            <tr>
                                <td><?php echo e($row->facility_name); ?></td>
                                <td class="text-end"><?php echo e(number_format($row->redemption_count)); ?></td>
                                <td class="text-end"><?php echo e(number_format($row->total_pax)); ?></td>
                                <td class="text-end">
                                    <div class="progress" style="height:18px;">
                                        <div class="progress-bar bg-info" style="width:<?php echo e($share); ?>%"><?php echo e($share); ?>%</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No redemptions in this period.</td></tr>
                        <?php endif; ?>
                        </tbody>
                        <?php if($redemptions->isNotEmpty()): ?>
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td>Total</td>
                                <td class="text-end"><?php echo e(number_format($redemptions->sum('redemption_count'))); ?></td>
                                <td class="text-end"><?php echo e(number_format($redemptions->sum('total_pax'))); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-secondary h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-ticket-alt mr-1"></i> Voucher Status Overview</h3>
            </div>
            <div class="card-body p-0 table-responsive-stack">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th class="text-end">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $voucherStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $badge = match(is_object($row->status) ? $row->status->value : $row->status) {
                                'active' => 'success',
                                'redeemed' => 'primary',
                                'expired' => 'secondary',
                                'cancelled' => 'danger',
                                default => 'secondary',
                            };
                            $label = ucfirst(is_object($row->status) ? $row->status->value : $row->status);
                        ?>
                        <tr>
                            <td><span class="badge bg-<?php echo e($badge); ?>"><?php echo e($label); ?></span></td>
                            <td class="text-end"><?php echo e(number_format($row->total)); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="2" class="text-center text-muted py-4">No voucher data available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                    <?php if($voucherStats->isNotEmpty()): ?>
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td>Total</td>
                            <td class="text-end"><?php echo e(number_format($voucherStats->sum('total'))); ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    
    <div class="col-lg-7 mb-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-store mr-1"></i> Redemptions by Outlet</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Outlet</th>
                                <th>Facility</th>
                                <th class="text-end">Events</th>
                                <th class="text-end">Pax</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $redemptionsByOutlet; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($row->outlet_name); ?></td>
                                <td><span class="text-muted"><?php echo e($row->facility_name); ?></span></td>
                                <td class="text-end"><?php echo e(number_format($row->redemption_count)); ?></td>
                                <td class="text-end"><?php echo e(number_format($row->total_pax)); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No outlet activity in this period.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-lg-5 mb-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-day mr-1"></i> Daily Trend</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Events</th>
                                <th class="text-end">Pax</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $dailyTrend; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e(\Carbon\Carbon::parse($row->date)->format('D, M j')); ?></td>
                                <td class="text-end"><?php echo e($row->redemption_count); ?></td>
                                <td class="text-end"><?php echo e($row->total_pax); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No daily data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="card card-outline card-secondary mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Redemptions</h3>
        <div class="card-tools">
            <span class="badge bg-secondary">Latest 15</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date / Time</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Facility</th>
                        <th>Outlet</th>
                        <th class="text-end">Pax</th>
                        <th>Staff</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $recentRedemptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <small><?php echo e($log->date->format('Y-m-d')); ?></small><br>
                            <small class="text-muted"><?php echo e($log->time); ?></small>
                        </td>
                        <td><?php echo e($log->guest?->full_name ?? '—'); ?></td>
                        <td><?php echo e($log->booking?->room?->number ?? '—'); ?></td>
                        <td><?php echo e($log->facilityTemplate?->name ?? '—'); ?></td>
                        <td><?php echo e($log->outlet?->name ?? '—'); ?></td>
                        <td class="text-end"><span class="badge bg-info"><?php echo e($log->pax_used); ?></span></td>
                        <td><small><?php echo e($log->user?->name ?? 'System'); ?></small></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No recent redemptions in this period.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script nonce="<?php echo e($cspNonce); ?>">
function toggleFilterFields() {
    const type = document.getElementById('filter_type').value;
    const groups = {
        'date_range': ['filter-date-range', 'filter-date-range-to'],
        'month': ['filter-month', 'filter-month-year'],
        'year': ['filter-year-only'],
    };

    document.querySelectorAll('.filter-group').forEach(function (el) {
        el.style.display = 'none';
        el.querySelectorAll('input, select').forEach(function (input) {
            input.disabled = true;
        });
    });

    (groups[type] || []).forEach(function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = '';
            el.querySelectorAll('input, select').forEach(function (input) {
                input.disabled = false;
            });
        }
    });
}

document.getElementById('filter_type').addEventListener('change', toggleFilterFields);
toggleFilterFields();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views\reports\index.blade.php ENDPATH**/ ?>