<?php $__env->startSection('title', 'Redeem Voucher'); ?>
<?php $__env->startSection('page_title', 'Redeem Voucher'); ?>
<?php $__env->startSection('content'); ?>
<div class="card col-md-8">
    <div class="card-body">
        <p class="text-muted small">Scan or paste QR payload: <strong>Room+FacilityCode+Date</strong> (client format).</p>
        <form method="POST" action="<?php echo e(route('vouchers.redeem')); ?>">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label">QR Code</label>
                <input type="text" name="qr_code" class="form-control <?php $__errorArgs = ['qr_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('qr_code')); ?>" placeholder="J 01 - Forest Tent Japan+BREAKFAST+2026-06-04" required>
                <?php $__errorArgs = ['qr_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Outlet</label>
                <select name="outlet_id" class="form-select" required>
                    <?php $__currentLoopData = $outlets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outlet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($outlet->id); ?>"><?php echo e($outlet->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Pax used</label>
                <input type="number" name="pax_used" value="1" min="1" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Redeem</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/vouchers/redeem.blade.php ENDPATH**/ ?>