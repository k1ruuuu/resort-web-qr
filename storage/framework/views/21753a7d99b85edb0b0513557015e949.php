<table>
    <tr>
        <td colspan="11">RESORT VOUCHER ANALYTICS REPORT</td>
    </tr>
    <tr>
        <td colspan="11">Report Period: <?php echo e($periodLabel); ?></td>
    </tr>
    <tr>
        <td colspan="11">Generated on: <?php echo e($exportDate); ?></td>
    </tr>
    <tr>
        <td colspan="11">
            Filters:
            <?php if(!empty($filters['property_id'])): ?> Property ID <?php echo e($filters['property_id']); ?> <?php endif; ?>
            <?php if(!empty($filters['facility_id'])): ?> | Facility ID <?php echo e($filters['facility_id']); ?> <?php endif; ?>
            <?php if(!empty($filters['outlet_id'])): ?> | Outlet ID <?php echo e($filters['outlet_id']); ?> <?php endif; ?>
            <?php if(empty($filters['property_id']) && empty($filters['facility_id']) && empty($filters['outlet_id'])): ?> All properties <?php endif; ?>
        </td>
    </tr>
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">EXECUTIVE SUMMARY</td></tr>
    <tr>
        <th>Total Redemptions</th>
        <th>Total Pax</th>
        <th>Unique Guests</th>
        <th>Avg Pax / Redemption</th>
    </tr>
    <tr>
        <td><?php echo e($overview->total_redemptions); ?></td>
        <td><?php echo e($overview->total_pax); ?></td>
        <td><?php echo e($overview->unique_guests); ?></td>
        <td><?php echo e($overview->avg_pax_per_redemption); ?></td>
    </tr>
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">REDEMPTIONS BY FACILITY</td></tr>
    <tr>
        <th>Facility</th>
        <th>Events</th>
        <th>Pax</th>
    </tr>
    <?php $__empty_1 = true; $__currentLoopData = $redemptionsByFacility; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <tr>
        <td><?php echo e($row->facility_name); ?></td>
        <td><?php echo e($row->redemption_count); ?></td>
        <td><?php echo e($row->total_pax); ?></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <tr><td colspan="3">No data in selected period.</td></tr>
    <?php endif; ?>
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">REDEMPTIONS BY OUTLET</td></tr>
    <tr>
        <th>Outlet</th>
        <th>Facility</th>
        <th>Events</th>
        <th>Pax</th>
    </tr>
    <?php $__empty_1 = true; $__currentLoopData = $redemptionsByOutlet; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <tr>
        <td><?php echo e($row->outlet_name); ?></td>
        <td><?php echo e($row->facility_name); ?></td>
        <td><?php echo e($row->redemption_count); ?></td>
        <td><?php echo e($row->total_pax); ?></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <tr><td colspan="4">No data in selected period.</td></tr>
    <?php endif; ?>
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">DAILY REDEMPTION TREND</td></tr>
    <tr>
        <th>Date</th>
        <th>Events</th>
        <th>Pax</th>
    </tr>
    <?php $__empty_1 = true; $__currentLoopData = $dailyTrend; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <tr>
        <td><?php echo e(\Carbon\Carbon::parse($row->date)->format('Y-m-d')); ?></td>
        <td><?php echo e($row->redemption_count); ?></td>
        <td><?php echo e($row->total_pax); ?></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <tr><td colspan="3">No data in selected period.</td></tr>
    <?php endif; ?>
    <tr><td colspan="11"></td></tr>

    <tr><td colspan="11">DETAILED REDEMPTION LOG</td></tr>
    <tr>
        <th>No</th>
        <th>Date</th>
        <th>Time</th>
        <th>Guest Name</th>
        <th>Room</th>
        <th>Booking Code</th>
        <th>Facility</th>
        <th>Outlet</th>
        <th>Pax Used</th>
        <th>Remaining</th>
        <th>Staff</th>
    </tr>
    <?php $__currentLoopData = $redemptionDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $redemption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <tr>
        <td><?php echo e($index + 1); ?></td>
        <td><?php echo e($redemption->date->format('Y-m-d')); ?></td>
        <td><?php echo e($redemption->time); ?></td>
        <td><?php echo e($redemption->guest?->full_name ?? 'N/A'); ?></td>
        <td><?php echo e($redemption->booking?->room?->code ?? $redemption->booking?->room?->number ?? 'N/A'); ?></td>
        <td><?php echo e($redemption->booking?->booking_code ?? $redemption->booking?->reference ?? 'N/A'); ?></td>
        <td><?php echo e($redemption->facilityTemplate?->name ?? 'N/A'); ?></td>
        <td><?php echo e($redemption->outlet?->name ?? 'N/A'); ?></td>
        <td><?php echo e($redemption->pax_used); ?></td>
        <td><?php echo e($redemption->remaining_quota); ?></td>
        <td><?php echo e($redemption->user?->name ?? 'System'); ?></td>
    </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table>
<?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views\exports\reports-summary.blade.php ENDPATH**/ ?>