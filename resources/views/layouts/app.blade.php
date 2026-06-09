<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
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
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link">Logout</button>
                </form>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-text font-weight-light ms-2">{{ config('app.name') }}</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link @if(request()->routeIs('dashboard')) active @endif">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    @can('properties.manage')
                    <li class="nav-item">
                        <a href="{{ route('properties.index') }}" class="nav-link @if(request()->routeIs('properties.*')) active @endif">
                            <i class="nav-icon fas fa-hotel"></i>
                            <p>Properties</p>
                        </a>
                    </li>
                    @endcan
                    @can('rooms.manage')
                    <li class="nav-item">
                        <a href="{{ route('rooms.index') }}" class="nav-link @if(request()->routeIs('rooms.*')) active @endif">
                            <i class="nav-icon fas fa-door-open"></i>
                            <p>Rooms</p>
                        </a>
                    </li>
                    @endcan
                    @can('facilities.manage')
                    <li class="nav-item">
                        <a href="{{ route('facilities.index') }}" class="nav-link @if(request()->routeIs('facilities.*')) active @endif">
                            <i class="nav-icon fas fa-concierge-bell"></i>
                            <p>Facilities</p>
                        </a>
                    </li>
                    @endcan
                    @can('guests.manage')
                    <li class="nav-item">
                        <a href="{{ route('guests.index') }}" class="nav-link @if(request()->routeIs('guests.*')) active @endif">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Guests</p>
                        </a>
                    </li>
                    @endcan
                    @can('bookings.view')
                    <li class="nav-item">
                        <a href="{{ route('bookings.index') }}" class="nav-link @if(request()->routeIs('bookings.*')) active @endif">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>Bookings</p>
                        </a>
                    </li>
                    @endcan
                    @can('vouchers.view')
                    <li class="nav-item">
                        <a href="{{ route('vouchers.index') }}" class="nav-link @if(request()->routeIs('vouchers.index') || request()->routeIs('vouchers.show')) active @endif">
                            <i class="nav-icon fas fa-qrcode"></i>
                            <p>Vouchers</p>
                        </a>
                    </li>
                    @endcan
                    @can('vouchers.redeem')
                    <li class="nav-item">
                        <a href="{{ route('vouchers.redeem.form') }}" class="nav-link @if(request()->routeIs('vouchers.redeem.form')) active @endif">
                            <i class="nav-icon fas fa-check-circle"></i>
                            <p>Redeem QR (Manual)</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('vouchers.scan.form') }}" class="nav-link @if(request()->routeIs('vouchers.scan.form')) active @endif">
                            <i class="nav-icon fas fa-camera"></i>
                            <p>Scan QR Code</p>
                        </a>
                    </li>
                    @endcan
                    @can('reports.view')
                    <li class="nav-item">
                        <a href="{{ route('reports.index') }}" class="nav-link @if(request()->routeIs('reports.index')) active @endif">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Reports</p>
                        </a>
                    </li>
                    @endcan
                    @can('delivery_logs.view')
                    <li class="nav-item">
                        <a href="{{ route('reports.delivery-logs') }}" class="nav-link @if(request()->routeIs('reports.delivery-logs')) active @endif">
                            <i class="nav-icon fas fa-paper-plane"></i>
                            <p>Delivery Logs</p>
                        </a>
                    </li>
                    @endcan
                    @can('reports.view')
                    <li class="nav-item">
                        <a href="{{ route('reports.scan-history') }}" class="nav-link @if(request()->routeIs('reports.scan-history')) active @endif">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Scan History</p>
                        </a>
                    </li>
                    @endcan
                    @can('delivery_settings.manage')
                    <li class="nav-item">
                        <a href="{{ route('settings.delivery') }}" class="nav-link @if(request()->routeIs('settings.delivery')) active @endif">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Delivery Settings</p>
                        </a>
                    </li>
                    @endcan
                    @if(auth()->user()?->can('users.manage') || auth()->user()?->can('roles.manage'))
                    <li class="nav-item has-treeview @if(request()->routeIs('users.*') || request()->routeIs('roles.*')) menu-is-opening menu-open @endif">
                        <a href="#" class="nav-link @if(request()->routeIs('users.*') || request()->routeIs('roles.*')) active @endif">
                            <i class="nav-icon fas fa-user-cog"></i>
                            <p>
                                User Management
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview" style="display: @if(request()->routeIs('users.*') || request()->routeIs('roles.*')) block @else none @endif;">
                            @can('users.manage')
                            <li class="nav-item">
                                <a href="{{ route('users.index') }}" class="nav-link @if(request()->routeIs('users.*')) active @endif">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Users</p>
                                </a>
                            </li>
                            @endcan
                            @can('roles.manage')
                            <li class="nav-item">
                                <a href="{{ route('roles.index') }}" class="nav-link @if(request()->routeIs('roles.*')) active @endif">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Roles</p>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endif
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1 class="m-0">@yield('page_title')</h1>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @yield('content')
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
@stack('scripts')
</body>
</html>
