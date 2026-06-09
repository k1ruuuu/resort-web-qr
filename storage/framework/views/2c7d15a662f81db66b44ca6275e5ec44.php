<?php $__env->startSection('title', 'Bookings'); ?>
<?php $__env->startSection('page_title', 'Bookings'); ?>
<?php $__env->startSection('content'); ?>
<div class="mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bookings.create')): ?>
    <a href="<?php echo e(route('bookings.create')); ?>" class="btn btn-primary btn-responsive">
        <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">New</span> Booking
    </a>
    <?php else: ?>
    <div></div>
    <?php endif; ?>
    
    <button class="btn btn-outline-secondary btn-responsive filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
        <i class="fas fa-filter"></i> Filters
    </button>
</div>

<!-- Search & Filter Form -->
<div class="collapse filter-collapse mb-3 <?php echo e(request()->hasAny(['search', 'status', 'property_id', 'date_from', 'date_to']) ? 'show' : ''); ?>" id="filterCollapse">
    <div class="card card-responsive">
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('bookings.index')); ?>">
                <div class="row g-3 form-row-responsive">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Name, email, room..."
                               value="<?php echo e(request('search')); ?>">
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" <?php echo e(request('status') === 'pending' ? 'selected' : ''); ?>>Pending</option>
                            <option value="confirmed" <?php echo e(request('status') === 'confirmed' ? 'selected' : ''); ?>>Confirmed</option>
                            <option value="checked_in" <?php echo e(request('status') === 'checked_in' ? 'selected' : ''); ?>>Checked In</option>
                            <option value="checked_out" <?php echo e(request('status') === 'checked_out' ? 'selected' : ''); ?>>Checked Out</option>
                            <option value="cancelled" <?php echo e(request('status') === 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">Property</label>
                        <select name="property_id" class="form-select">
                            <option value="">All</option>
                            <?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($property->id); ?>" <?php echo e(request('property_id') == $property->id ? 'selected' : ''); ?>>
                                    <?php echo e($property->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" 
                               name="date_from" 
                               class="form-control" 
                               value="<?php echo e(request('date_from')); ?>">
                    </div>
                    
                    <div class="col-6 col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" 
                               name="date_to" 
                               class="form-control" 
                               value="<?php echo e(request('date_to')); ?>">
                    </div>
                    
                    <div class="col-12 col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i><span class="d-none d-lg-inline ms-1">Go</span>
                        </button>
                    </div>
                </div>
                
                <?php if(request()->hasAny(['search', 'status', 'property_id', 'date_from', 'date_to'])): ?>
                <div class="mt-2">
                    <a href="<?php echo e(route('bookings.index')); ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="card card-responsive">
    <div class="card-body p-0">
        <div class="table-responsive overflow-auto-mobile">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Guest</th>
                        <th class="d-none d-md-table-cell">Property</th>
                        <th class="d-none d-lg-table-cell">Stay</th>
                        <th class="d-none d-sm-table-cell">Pax</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><strong class="text-truncate d-inline-block" style="max-width: 100px;"><?php echo e($booking->reference); ?></strong></td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" title="<?php echo e($booking->guest->full_name); ?>">
                                <?php echo e($booking->guest->full_name); ?>

                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="text-truncate" style="max-width: 120px;" title="<?php echo e($booking->property->name); ?>">
                                <?php echo e($booking->property->name); ?>

                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <small><?php echo e($booking->check_in->format('M d')); ?> – <?php echo e($booking->check_out->format('M d')); ?></small>
                        </td>
                        <td class="d-none d-sm-table-cell"><?php echo e($booking->total_pax); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($booking->status->value === 'pending' ? 'warning' : 'success'); ?> text-white">
                                <span class="d-none d-sm-inline"><?php echo e($booking->status->value); ?></span>
                                <span class="d-inline d-sm-none"><?php echo e(substr($booking->status->value, 0, 1)); ?></span>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?php echo e(route('bookings.show', $booking)); ?>" class="btn btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo e(route('bookings.edit', $booking)); ?>" class="btn btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="<?php echo e(route('bookings.destroy', $booking)); ?>" class="d-inline" onsubmit="return confirm('Delete this booking?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <?php if(request()->hasAny(['search', 'status', 'property_id', 'date_from', 'date_to'])): ?>
                                No bookings found matching your filters.
                            <?php else: ?>
                                No bookings found.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($bookings->hasPages()): ?>
    <div class="card-footer"><?php echo e($bookings->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/bookings/index.blade.php ENDPATH**/ ?>