<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('img/chanaya-logo.png') }}" type="image/png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-forest.css') }}">
</head>
<body>
<div class="wrapper">
    <nav class="main-header navbar navbar-expand-lg bg-transparent">
        <div class="container-fluid">
            <a href="{{ route('dashboard') }}" class="navbar-brand">
                <img src="{{ asset('img/chanaya-logo.png') }}" alt="Logo" class="brand-image" style="opacity: .9; max-height: 40px; width: auto;">
                <span class="brand-text font-weight-light ms-2" style="font-size: 1.1rem;">E-Voucher</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('docs')) active @endif" href="{{ route('docs') }}">
                            <i class="fas fa-book me-1"></i> Dokumentasi
                        </a>
                    </li>

                    {{-- Property Management --}}
                    @canany(['properties.manage', 'rooms.manage', 'facilities.manage', 'outlets.manage'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('properties.*') || request()->routeIs('rooms.*') || request()->routeIs('facilities.*') || request()->routeIs('outlets.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-hotel me-1"></i> Property
                        </a>
                        <ul class="dropdown-menu">
                            @can('properties.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('properties.*')) active @endif" href="{{ route('properties.index') }}"><i class="far fa-circle me-2"></i> Properties</a></li>
                            @endcan
                            @can('rooms.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('rooms.*')) active @endif" href="{{ route('rooms.index') }}"><i class="far fa-circle me-2"></i> Rooms</a></li>
                            @endcan
                            @can('facilities.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('facilities.*')) active @endif" href="{{ route('facilities.index') }}"><i class="far fa-circle me-2"></i> Facilities</a></li>
                            <li><a class="dropdown-item @if(request()->routeIs('outlets.*')) active @endif" href="{{ route('outlets.index') }}"><i class="far fa-circle me-2"></i> Outlets</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    {{-- Guest & Booking --}}
                    @canany(['guests.manage', 'bookings.view'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('guests.*') || request()->routeIs('bookings.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users me-1"></i> Guest & Booking
                        </a>
                        <ul class="dropdown-menu">
                            @can('guests.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('guests.*')) active @endif" href="{{ route('guests.index') }}"><i class="far fa-circle me-2"></i> Guests</a></li>
                            @endcan
                            @can('bookings.view')
                            <li><a class="dropdown-item @if(request()->routeIs('bookings.*')) active @endif" href="{{ route('bookings.index') }}"><i class="far fa-circle me-2"></i> Bookings</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    {{-- Voucher Management --}}
                    @canany(['vouchers.view', 'vouchers.redeem'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('vouchers.*') && !request()->routeIs('vouchers.public*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-qrcode me-1"></i> Voucher
                        </a>
                        <ul class="dropdown-menu">
                            @can('vouchers.view')
                            <li><a class="dropdown-item @if(request()->routeIs('vouchers.index') || request()->routeIs('vouchers.show') || request()->routeIs('vouchers.edit')) active @endif" href="{{ route('vouchers.index') }}"><i class="far fa-circle me-2"></i> Vouchers</a></li>
                            @endcan
                            @can('vouchers.redeem')
                            <li><a class="dropdown-item @if(request()->routeIs('vouchers.redeem.form')) active @endif" href="{{ route('vouchers.redeem.form') }}"><i class="far fa-circle me-2"></i> Redeem QR (Manual)</a></li>
                            <li><a class="dropdown-item @if(request()->routeIs('vouchers.scan.form')) active @endif" href="{{ route('vouchers.scan.form') }}"><i class="far fa-circle me-2"></i> Scan QR Code</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    {{-- Reports --}}
                    @canany(['reports.view', 'delivery_logs.view', 'import_logs.view'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('reports.*') || request()->routeIs('import-logs.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                        <ul class="dropdown-menu">
                            @can('reports.view')
                            <li><a class="dropdown-item @if(request()->routeIs('reports.index')) active @endif" href="{{ route('reports.index') }}"><i class="far fa-circle me-2"></i> Reports</a></li>
                            <li><a class="dropdown-item @if(request()->routeIs('reports.scan-history')) active @endif" href="{{ route('reports.scan-history') }}"><i class="far fa-circle me-2"></i> Scan History</a></li>
                            @endcan
                            @can('delivery_logs.view')
                            <li><a class="dropdown-item @if(request()->routeIs('reports.delivery-logs')) active @endif" href="{{ route('reports.delivery-logs') }}"><i class="far fa-circle me-2"></i> Delivery Logs</a></li>
                            @endcan
                            @can('import_logs.view')
                            <li><a class="dropdown-item @if(request()->routeIs('import-logs.*')) active @endif" href="{{ route('import-logs.index') }}"><i class="far fa-circle me-2"></i> Import Logs</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    @can('delivery_settings.manage')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('settings.delivery')) active @endif" href="{{ route('settings.delivery') }}">
                            <i class="fas fa-cog me-1"></i> Delivery Settings
                        </a>
                    </li>
                    @endcan

                    @if(auth()->user()?->can('users.manage') || auth()->user()?->can('roles.manage'))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('users.*') || request()->routeIs('roles.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-cog me-1"></i> User Management
                        </a>
                        <ul class="dropdown-menu">
                            @can('users.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('users.*')) active @endif" href="{{ route('users.index') }}"><i class="far fa-circle me-2"></i> Users</a></li>
                            @endcan
                            @can('roles.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('roles.*')) active @endif" href="{{ route('roles.index') }}"><i class="far fa-circle me-2"></i> Roles</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endif
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>