<?php $__env->startSection('title', 'Users'); ?>
<?php $__env->startSection('page_title', 'User Management'); ?>
<?php $__env->startSection('content'); ?>

<!-- Page Header with Actions -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search by name or email...">
        </div>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo e(route('users.create')); ?>" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo e($users->total()); ?></h3>
                <p>Total Users</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo e($users->where('is_active', true)->count()); ?></h3>
                <p>Active Users</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo e($users->where('is_active', false)->count()); ?></h3>
                <p>Inactive Users</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-slash"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3><?php echo e($users->filter(fn($u) => $u->roles->isEmpty())->count()); ?></h3>
                <p>No Role Assigned</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-times"></i>
            </div>
        </div>
    </div>
</div>

<!-- Users Table Card -->
<div class="card shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h3 class="card-title mb-0">
            <i class="fas fa-list"></i> All Users
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 5%">#</th>
                        <th style="width: 25%">
                            <i class="fas fa-user text-muted"></i> User
                        </th>
                        <th style="width: 25%">
                            <i class="fas fa-envelope text-muted"></i> Email
                        </th>
                        <th style="width: 20%">
                            <i class="fas fa-user-tag text-muted"></i> Roles
                        </th>
                        <th style="width: 10%">
                            <i class="fas fa-toggle-on text-muted"></i> Status
                        </th>
                        <th class="text-center" style="width: 15%">
                            <i class="fas fa-cog text-muted"></i> Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="align-middle">
                        <td class="ps-3 text-muted"><?php echo e($users->firstItem() + $index); ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-primary text-white me-2" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px;">
                                    <?php echo e(strtoupper(substr($user->name, 0, 1))); ?>

                                </div>
                                <div>
                                    <strong class="d-block"><?php echo e($user->name); ?></strong>
                                    <?php if(auth()->id() === $user->id): ?>
                                        <small class="badge badge-sm bg-gradient-primary">
                                            <i class="fas fa-star"></i> You
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted">
                                <i class="fas fa-envelope-open-text me-1"></i>
                                <?php echo e($user->email); ?>

                            </span>
                        </td>
                        <td>
                            <?php $__empty_2 = true; $__currentLoopData = $user->roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                <span class="badge bg-info me-1">
                                    <i class="fas fa-tag"></i> <?php echo e($role->name); ?>

                                </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-minus-circle"></i> No Role
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($user->is_active): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Active
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-times-circle"></i> Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="<?php echo e(route('users.edit', $user)); ?>" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="Edit User"
                                   data-bs-toggle="tooltip">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if(auth()->id() !== $user->id): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger" 
                                            title="Delete User"
                                            data-bs-toggle="tooltip"
                                            onclick="confirmDelete(<?php echo e($user->id); ?>, '<?php echo e($user->name); ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <form id="delete-form-<?php echo e($user->id); ?>" 
                                          method="POST" 
                                          action="<?php echo e(route('users.destroy', $user)); ?>" 
                                          style="display: none;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary disabled" 
                                            title="You cannot delete yourself" 
                                            disabled
                                            data-bs-toggle="tooltip">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                <h5>No Users Found</h5>
                                <p>Start by adding your first user.</p>
                                <a href="<?php echo e(route('users.create')); ?>" class="btn btn-primary mt-2">
                                    <i class="fas fa-user-plus"></i> Add User
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($users->hasPages()): ?>
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing <?php echo e($users->firstItem()); ?> to <?php echo e($users->lastItem()); ?> of <?php echo e($users->total()); ?> users
                </div>
                <div>
                    <?php echo e($users->links()); ?>

                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#usersTable tbody tr');
    
    tableRows.forEach(row => {
        const name = row.cells[1]?.textContent.toLowerCase() || '';
        const email = row.cells[2]?.textContent.toLowerCase() || '';
        
        if (name.includes(searchValue) || email.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Delete confirmation
function confirmDelete(userId, userName) {
    if (confirm(`Are you sure you want to delete user "${userName}"?\n\nThis action cannot be undone.`)) {
        document.getElementById('delete-form-' + userId).submit();
    }
}

// Initialize Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\jriw\resort-project\resources\views/users/index.blade.php ENDPATH**/ ?>