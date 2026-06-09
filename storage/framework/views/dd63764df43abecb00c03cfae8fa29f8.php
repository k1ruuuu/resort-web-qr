<?php $__env->startSection('title', 'Edit User'); ?>
<?php $__env->startSection('page_title', 'Edit User'); ?>
<?php $__env->startSection('content'); ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="<?php echo e(route('users.index')); ?>">
                <i class="fas fa-users"></i> Users
            </a>
        </li>
        <li class="breadcrumb-item active">Edit: <?php echo e($user->name); ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-10 col-xl-8">
        <!-- User Info Card -->
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-circle bg-primary text-white me-3" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 24px;">
                        <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                    </div>
                    <div>
                        <h4 class="mb-1"><?php echo e($user->name); ?></h4>
                        <p class="text-muted mb-0">
                            <i class="fas fa-envelope me-1"></i> <?php echo e($user->email); ?>

                        </p>
                    </div>
                    <?php if(auth()->id() === $user->id): ?>
                        <span class="badge bg-gradient-primary ms-auto">
                            <i class="fas fa-star"></i> Current User
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Edit Form Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h3 class="card-title mb-0">
                    <i class="fas fa-user-edit"></i> Edit User Information
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(route('users.update', $user)); ?>" id="editUserForm">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    
                    <!-- Personal Information Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-id-card text-primary"></i> Personal Information
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           name="name" 
                                           class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           value="<?php echo e(old('name', $user->name)); ?>" 
                                           placeholder="Enter full name"
                                           required>
                                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           name="email" 
                                           class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           value="<?php echo e(old('email', $user->email)); ?>" 
                                           placeholder="user@example.com"
                                           required>
                                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-lock text-warning"></i> Change Password
                        </h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Note:</strong> Leave password fields blank to keep the current password.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" 
                                           name="password" 
                                           class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                           placeholder="Leave blank to keep current"
                                           id="password">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Must be at least 8 characters if changing
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" 
                                           name="password_confirmation" 
                                           class="form-control" 
                                           placeholder="Re-enter new password"
                                           id="password_confirmation">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            onclick="togglePassword('password_confirmation')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Status Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-toggle-on text-success"></i> Account Status
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <select name="is_active" class="form-select <?php $__errorArgs = ['is_active'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        <?php echo e(auth()->id() === $user->id ? 'disabled' : ''); ?>>
                                    <option value="1" <?php echo e(old('is_active', $user->is_active) ? 'selected' : ''); ?>>
                                        Active
                                    </option>
                                    <option value="0" <?php echo e(!old('is_active', $user->is_active) ? 'selected' : ''); ?>>
                                        Inactive
                                    </option>
                                </select>
                                <?php if(auth()->id() === $user->id): ?>
                                    <small class="form-text text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> You cannot deactivate your own account
                                    </small>
                                <?php else: ?>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Inactive users cannot log in
                                    </small>
                                <?php endif; ?>
                                <?php $__errorArgs = ['is_active'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Roles Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-user-tag text-info"></i> Role Assignment
                        </h5>
                        <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="roles[]" 
                                       value="<?php echo e($role->name); ?>" 
                                       id="role_<?php echo e($role->id); ?>" 
                                       <?php echo e((is_array(old('roles')) && in_array($role->name, old('roles'))) || (!is_array(old('roles')) && $user->hasRole($role->name)) ? 'checked' : ''); ?>

                                       style="width: 3em; height: 1.5em;">
                                <label class="form-check-label ms-2" for="role_<?php echo e($role->id); ?>">
                                    <strong><?php echo e($role->name); ?></strong>
                                    <?php if($role->permissions->count() > 0): ?>
                                        <small class="text-muted d-block">
                                            <?php echo e($role->permissions->count()); ?> permissions
                                        </small>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                No roles available. Please create roles first.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Form Actions -->
                    <div class="border-top pt-3 mt-4">
                        <button type="submit" class="btn btn-warning px-4">
                            <i class="fas fa-save"></i> Update User
                        </button>
                        <a href="<?php echo e(route('users.index')); ?>" class="btn btn-secondary px-4">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <?php if(auth()->id() !== $user->id): ?>
                            <button type="button" 
                                    class="btn btn-outline-danger px-4 float-end" 
                                    onclick="confirmDelete()">
                                <i class="fas fa-trash-alt"></i> Delete User
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if(auth()->id() !== $user->id): ?>
                    <form id="delete-form" method="POST" action="<?php echo e(route('users.destroy', $user)); ?>" style="display: none;">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function confirmDelete() {
    if (confirm('Are you sure you want to delete user "<?php echo e($user->name); ?>"?\n\nThis action cannot be undone.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-web-qr\resources\views/users/edit.blade.php ENDPATH**/ ?>