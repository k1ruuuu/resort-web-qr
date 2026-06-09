<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', config('app.name')); ?></title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/responsive.css')); ?>">
    <style>
        .nav-sidebar .nav-treeview {
            transition: all 0.3s ease-in-out;
        }
        
        .nav-sidebar .has-treeview > a .right {
            transition: transform 0.3s ease-in-out;
        }
        
        .nav-sidebar .has-treeview.menu-open > a .right {
            transform: rotate(-90deg);
        }
        
        .nav-treeview > .nav-item > .nav-link {
            padding-left: 3rem;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <form method="POST" action="<?php echo e(route('logout')); ?>" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-link nav-link">Logout</button>
                </form>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="<?php echo e(route('dashboard')); ?>" class="brand-link">
            <span class="brand-text font-weight-light ms-2"><?php echo e(config('app.name')); ?></span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
                    <li class="nav-item">
                        <a href="<?php echo e(route('dashboard')); ?>" class="nav-link <?php if(request()->routeIs('dashboard')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('properties.manage')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('properties.index')); ?>" class="nav-link <?php if(request()->routeIs('properties.*')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-hotel"></i>
                            <p>Properties</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rooms.manage')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('rooms.index')); ?>" class="nav-link <?php if(request()->routeIs('rooms.*')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-door-open"></i>
                            <p>Rooms</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('facilities.manage')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('facilities.index')); ?>" class="nav-link <?php if(request()->routeIs('facilities.*')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-concierge-bell"></i>
                            <p>Facilities</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('guests.manage')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('guests.index')); ?>" class="nav-link <?php if(request()->routeIs('guests.*')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Guests</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bookings.view')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('bookings.index')); ?>" class="nav-link <?php if(request()->routeIs('bookings.*')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>Bookings</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vouchers.view')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('vouchers.index')); ?>" class="nav-link <?php if(request()->routeIs('vouchers.index') || request()->routeIs('vouchers.show')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-qrcode"></i>
                            <p>Vouchers</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vouchers.redeem')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('vouchers.redeem.form')); ?>" class="nav-link <?php if(request()->routeIs('vouchers.redeem.form')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-check-circle"></i>
                            <p>Redeem QR (Manual)</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo e(route('vouchers.scan.form')); ?>" class="nav-link <?php if(request()->routeIs('vouchers.scan.form')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-camera"></i>
                            <p>Scan QR Code</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reports.view')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('reports.index')); ?>" class="nav-link <?php if(request()->routeIs('reports.index')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Reports</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delivery_logs.view')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('reports.delivery-logs')); ?>" class="nav-link <?php if(request()->routeIs('reports.delivery-logs')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-paper-plane"></i>
                            <p>Delivery Logs</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reports.view')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('reports.scan-history')); ?>" class="nav-link <?php if(request()->routeIs('reports.scan-history')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Scan History</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delivery_settings.manage')): ?>
                    <li class="nav-item">
                        <a href="<?php echo e(route('settings.delivery')); ?>" class="nav-link <?php if(request()->routeIs('settings.delivery')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Delivery Settings</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(auth()->user()?->can('users.manage') || auth()->user()?->can('roles.manage')): ?>
                    <li class="nav-item has-treeview <?php if(request()->routeIs('users.*') || request()->routeIs('roles.*')): ?> menu-is-opening menu-open <?php endif; ?>">
                        <a href="#" class="nav-link <?php if(request()->routeIs('users.*') || request()->routeIs('roles.*')): ?> active <?php endif; ?>">
                            <i class="nav-icon fas fa-user-cog"></i>
                            <p>
                                User Management
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview" style="display: <?php if(request()->routeIs('users.*') || request()->routeIs('roles.*')): ?> block <?php else: ?> none <?php endif; ?>;">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.manage')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(route('users.index')); ?>" class="nav-link <?php if(request()->routeIs('users.*')): ?> active <?php endif; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Users</p>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('roles.manage')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(route('roles.index')); ?>" class="nav-link <?php if(request()->routeIs('roles.*')): ?> active <?php endif; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Roles</p>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1 class="m-0"><?php echo $__env->yieldContent('page_title'); ?></h1>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <?php if(session('success')): ?>
                    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
                <?php endif; ?>
                <?php if(session('error')): ?>
                    <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
                <?php endif; ?>
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </section>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    $('[data-widget="treeview"]').Treeview('init');
    
    $('.nav-sidebar .has-treeview > a').on('click', function(e) {
        e.preventDefault();
        
        var $parent = $(this).parent();
        var $treeview = $parent.find('> .nav-treeview');
        
        $('.nav-sidebar .has-treeview').not($parent).removeClass('menu-is-opening menu-open');
        $('.nav-sidebar .nav-treeview').not($treeview).slideUp(300);
        
        if ($parent.hasClass('menu-open')) {
            $parent.removeClass('menu-is-opening menu-open');
            $treeview.slideUp(300);
        } else {
            $parent.addClass('menu-is-opening menu-open');
            $treeview.slideDown(300, function() {
                $parent.removeClass('menu-is-opening');
            });
        }
    });
});
</script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\jriw\resort-web-qr\resources\views/layouts/app.blade.php ENDPATH**/ ?>