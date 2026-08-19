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
    <link rel="stylesheet" href="{{ asset('css/theme-forest.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
</head>
<body>
<div class="wrapper">
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%); border-bottom: 3px solid #d4a373; padding: 0.6rem 1rem;">
        <div class="container-fluid">
            <a href="{{ route('dashboard') }}" class="navbar-brand d-flex align-items-center gap-2">
                <img src="{{ asset('img/chanaya-logo.png') }}" alt="Logo" style="background: white; padding: 4px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-height: 38px; width: auto;">
                <span class="brand-text fw-bold text-white" style="font-size: 1.15rem; letter-spacing: -0.02em;">E-Voucher</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="border-color: rgba(255,255,255,0.3); padding: 0.4rem 0.6rem;">
                <span class="navbar-toggler-icon" style="filter: brightness(0) invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}" style="color: rgba(255,255,255,0.85); font-weight: 600; font-size: 0.9rem; padding: 0.5rem 0.85rem !important; border-radius: 6px; transition: all 0.2s ease;">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('docs')) active @endif" href="{{ route('docs') }}" style="color: rgba(255,255,255,0.85); font-weight: 600; font-size: 0.9rem; padding: 0.5rem 0.85rem !important; border-radius: 6px; transition: all 0.2s ease;">
                            <i class="fas fa-book me-1"></i> Dokumentasi
                        </a>
                    </li>

                    {{-- Property Management --}}
                    @canany(['properties.manage', 'rooms.manage', 'facilities.manage', 'outlets.manage'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('properties.*') || request()->routeIs('rooms.*') || request()->routeIs('facilities.*') || request()->routeIs('outlets.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: rgba(255,255,255,0.85); font-weight: 600; font-size: 0.9rem; padding: 0.5rem 0.85rem !important; border-radius: 6px;">
                            <i class="fas fa-hotel me-1"></i> Property
                        </a>
                        <ul class="dropdown-menu" style="border: none; border-radius: 10px; box-shadow: 0 8px 24px rgba(27, 67, 50, 0.15); padding: 0.5rem; margin-top: 0.5rem; background: white;">
                            @can('properties.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('properties.*')) active @endif" href="{{ route('properties.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Properties</a></li>
                            @endcan
                            @can('rooms.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('rooms.*')) active @endif" href="{{ route('rooms.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Rooms</a></li>
                            @endcan
                            @can('facilities.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('facilities.*')) active @endif" href="{{ route('facilities.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Facilities</a></li>
                            <li><a class="dropdown-item @if(request()->routeIs('outlets.*')) active @endif" href="{{ route('outlets.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Outlets</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    {{-- Guest & Booking --}}
                    @canany(['guests.manage', 'bookings.view'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('guests.*') || request()->routeIs('bookings.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: rgba(255,255,255,0.85); font-weight: 600; font-size: 0.9rem; padding: 0.5rem 0.85rem !important; border-radius: 6px;">
                            <i class="fas fa-users me-1"></i> Guest & Booking
                        </a>
                        <ul class="dropdown-menu" style="border: none; border-radius: 10px; box-shadow: 0 8px 24px rgba(27, 67, 50, 0.15); padding: 0.5rem; margin-top: 0.5rem; background: white;">
                            @can('guests.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('guests.*')) active @endif" href="{{ route('guests.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Guests</a></li>
                            @endcan
                            @can('bookings.view')
                            <li><a class="dropdown-item @if(request()->routeIs('bookings.*')) active @endif" href="{{ route('bookings.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Bookings</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    {{-- Voucher Management --}}
                    @canany(['vouchers.view', 'vouchers.redeem'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('vouchers.*') && !request()->routeIs('vouchers.public*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: rgba(255,255,255,0.85); font-weight: 600; font-size: 0.9rem; padding: 0.5rem 0.85rem !important; border-radius: 6px;">
                            <i class="fas fa-qrcode me-1"></i> Voucher
                        </a>
                        <ul class="dropdown-menu" style="border: none; border-radius: 10px; box-shadow: 0 8px 24px rgba(27, 67, 50, 0.15); padding: 0.5rem; margin-top: 0.5rem; background: white;">
                            @can('vouchers.view')
                            <li><a class="dropdown-item @if(request()->routeIs('vouchers.index') || request()->routeIs('vouchers.show') || request()->routeIs('vouchers.edit')) active @endif" href="{{ route('vouchers.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Vouchers</a></li>
                            @endcan
                            @can('vouchers.redeem')
                            <li><a class="dropdown-item @if(request()->routeIs('vouchers.redeem.form')) active @endif" href="{{ route('vouchers.redeem.form') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Redeem QR (Manual)</a></li>
                            <li><a class="dropdown-item @if(request()->routeIs('vouchers.scan.form')) active @endif" href="{{ route('vouchers.scan.form') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Scan QR Code</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    {{-- Reports --}}
                    @canany(['reports.view', 'delivery_logs.view', 'import_logs.view'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('reports.*') || request()->routeIs('import-logs.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: rgba(255,255,255,0.85); font-weight: 600; font-size: 0.9rem; padding: 0.5rem 0.85rem !important; border-radius: 6px;">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                        <ul class="dropdown-menu" style="border: none; border-radius: 10px; box-shadow: 0 8px 24px rgba(27, 67, 50, 0.15); padding: 0.5rem; margin-top: 0.5rem; background: white;">
                            @can('reports.view')
                            <li><a class="dropdown-item @if(request()->routeIs('reports.index')) active @endif" href="{{ route('reports.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Reports</a></li>
                            <li><a class="dropdown-item @if(request()->routeIs('reports.scan-history')) active @endif" href="{{ route('reports.scan-history') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Scan History</a></li>
                            @endcan
                            @can('delivery_logs.view')
                            <li><a class="dropdown-item @if(request()->routeIs('reports.delivery-logs')) active @endif" href="{{ route('reports.delivery-logs') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Delivery Logs</a></li>
                            @endcan
                            @can('import_logs.view')
                            <li><a class="dropdown-item @if(request()->routeIs('import-logs.*')) active @endif" href="{{ route('import-logs.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Import Logs</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    @can('delivery_settings.manage')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('settings.delivery')) active @endif" href="{{ route('settings.delivery') }}" style="color: rgba(255,255,255,0.85); font-weight: 600; font-size: 0.9rem; padding: 0.5rem 0.85rem !important; border-radius: 6px; transition: all 0.2s ease;">
                            <i class="fas fa-cog me-1"></i> Delivery Settings
                        </a>
                    </li>
                    @endcan

                    @if(auth()->user()?->can('users.manage') || auth()->user()?->can('roles.manage'))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if(request()->routeIs('users.*') || request()->routeIs('roles.*')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: rgba(255,255,255,0.85); font-weight: 600; font-size: 0.9rem; padding: 0.5rem 0.85rem !important; border-radius: 6px;">
                            <i class="fas fa-user-cog me-1"></i> User Management
                        </a>
                        <ul class="dropdown-menu" style="border: none; border-radius: 10px; box-shadow: 0 8px 24px rgba(27, 67, 50, 0.15); padding: 0.5rem; margin-top: 0.5rem; background: white;">
                            @can('users.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('users.*')) active @endif" href="{{ route('users.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Users</a></li>
                            @endcan
                            @can('roles.manage')
                            <li><a class="dropdown-item @if(request()->routeIs('roles.*')) active @endif" href="{{ route('roles.index') }}" style="border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 0.55rem 1rem; color: #2d6a4f;"><i class="far fa-circle me-2" style="font-size: 0.75rem; opacity: 0.7;"></i> Roles</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endif
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link" style="color: rgba(255,255,255,0.85); font-weight: 600; text-decoration: none; padding: 0.5rem 0.85rem !important; border-radius: 6px; transition: all 0.2s ease;">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper" style="background: #faf5e9; min-height: calc(100vh - 60px);">
        <div class="content-header" style="background: white; padding: 1.25rem 0; margin-bottom: 1.5rem; border-bottom: 2px solid #d8f3dc;">
            <div class="container-fluid">
                <h1 class="m-0" style="color: #1b4332; font-weight: 800; font-size: 1.6rem;">@yield('page_title')</h1>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success" style="background: #d8f3dc; color: #1b4332; border: none; border-radius: 10px; font-weight: 600;">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger" style="background: #fef2f2; color: #991b1b; border: none; border-radius: 10px; font-weight: 600;">{{ session('error') }}</div>
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