
<?php $__env->startSection('title', 'Import Guests'); ?>
<?php $__env->startSection('page_title', 'Import Guests'); ?>
<?php $__env->startSection('content'); ?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Import Guests from File</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Instructions:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Download the template file to see the required format</li>
                        <li>Fill in your guest data in the template</li>
                        <li>Save as CSV, XLS, or XLSX format</li>
                        <li>Upload the file below to import</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <a href="<?php echo e(route('guests.download-template')); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                </div>

                <form method="POST" action="<?php echo e(route('guests.process-import')); ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    
                    <div class="mb-3">
                        <label for="file" class="form-label">Select File</label>
                        <input type="file" 
                               class="form-control <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                               id="file" 
                               name="file" 
                               accept=".csv,.xls,.xlsx"
                               required>
                        <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <small class="text-muted">Maximum file size: 10 MB. Supported formats: CSV, XLS, XLSX</small>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Note:</strong> Guests with duplicate email or phone numbers will be skipped.
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?php echo e(route('guests.index')); ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Import Guests
                        </button>
                    </div>
                </form>

                <?php if(session('import_failures') && count(session('import_failures')) > 0): ?>
                <div class="mt-4">
                    <h6 class="text-danger">Import Failures</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Field</th>
                                    <th>Errors</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = session('import_failures'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $failure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($failure['row']); ?></td>
                                    <td><?php echo e($failure['attribute']); ?></td>
                                    <td><?php echo e(implode(', ', $failure['errors'])); ?></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(session('import_errors') && count(session('import_errors')) > 0): ?>
                <div class="mt-4">
                    <h6 class="text-danger">General Errors</h6>
                    <ul class="text-danger">
                        <?php $__currentLoopData = session('import_errors'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\thinkpad\Documents\Pawbxj\resort-web-qr\resources\views\guests\import.blade.php ENDPATH**/ ?>