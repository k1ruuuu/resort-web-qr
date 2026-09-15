<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('dashboard') }}" class="brand-link">
        <img src="{{ asset('img/chanaya-logo.png') }}" alt="Logo" class="brand-image">
        <span class="brand-text">E-Voucher</span>
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
                <li class="nav-item">
                    <a href="{{ route('docs') }}" class="nav-link @if(request()->routeIs('docs')) active @endif">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Dokumentasi</p>
                    </a>
                </li>
                {{-- Property Management --}}
                @canany(['properties.manage', 'rooms.manage', 'facilities.manage'])
                @php $propertyActive = request()->routeIs('properties.*') || request()->routeIs('rooms.*') || request()->routeIs('facilities.*') || request()->routeIs('outlets.*'); @endphp
                <li class="nav-item has-treeview @if($propertyActive) menu-is-opening menu-open @endif">
                    <a href="#" class="nav-link @if($propertyActive) active @endif">
                        <i class="nav-icon fas fa-hotel"></i>
                        <p>
                            Property
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="display: @if($propertyActive) block @else none @endif;">
                        @can('properties.manage')
                        <li class="nav-item">
                            <a href="{{ route('properties.index') }}" class="nav-link @if(request()->routeIs('properties.*')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Properties</p>
                            </a>
                        </li>
                        @endcan
                        @can('rooms.manage')
                        <li class="nav-item">
                            <a href="{{ route('rooms.index') }}" class="nav-link @if(request()->routeIs('rooms.*')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Rooms</p>
                            </a>
                        </li>
                        @endcan
                        @can('facilities.manage')
                        <li class="nav-item">
                            <a href="{{ route('facilities.index') }}" class="nav-link @if(request()->routeIs('facilities.*')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Facilities</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('outlets.index') }}" class="nav-link @if(request()->routeIs('outlets.*')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Outlets</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                {{-- Guest & Booking --}}
                @canany(['guests.manage', 'bookings.view'])
                @php $guestBookingActive = request()->routeIs('guests.*') || request()->routeIs('bookings.*'); @endphp
                <li class="nav-item has-treeview @if($guestBookingActive) menu-is-opening menu-open @endif">
                    <a href="#" class="nav-link @if($guestBookingActive) active @endif">
                        <i class="nav-icon fas fa-users"></i>
                        <p>
                            Guest & Booking
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="display: @if($guestBookingActive) block @else none @endif;">
                        @can('guests.manage')
                        <li class="nav-item">
                            <a href="{{ route('guests.index') }}" class="nav-link @if(request()->routeIs('guests.*')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Guests</p>
                            </a>
                        </li>
                        @endcan
                        @can('bookings.view')
                        <li class="nav-item">
                            <a href="{{ route('bookings.index') }}" class="nav-link @if(request()->routeIs('bookings.*')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Bookings</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                {{-- Voucher Management --}}
                @canany(['vouchers.view', 'vouchers.redeem'])
                @php $voucherActive = request()->routeIs('vouchers.*') && !request()->routeIs('vouchers.public*'); @endphp
                <li class="nav-item has-treeview @if($voucherActive) menu-is-opening menu-open @endif">
                    <a href="#" class="nav-link @if($voucherActive) active @endif">
                        <i class="nav-icon fas fa-qrcode"></i>
                        <p>
                            Voucher
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="display: @if($voucherActive) block @else none @endif;">
                        @can('vouchers.view')
                        <li class="nav-item">
                            <a href="{{ route('vouchers.index') }}" class="nav-link @if(request()->routeIs('vouchers.index') || request()->routeIs('vouchers.show') || request()->routeIs('vouchers.edit')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Vouchers</p>
                            </a>
                        </li>
                        @endcan
                        @can('vouchers.redeem')
                        <li class="nav-item">
                            <a href="{{ route('vouchers.redeem.form') }}" class="nav-link @if(request()->routeIs('vouchers.redeem.form')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Redeem QR (Manual)</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('vouchers.scan.form') }}" class="nav-link @if(request()->routeIs('vouchers.scan.form')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Scan QR Code</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                {{-- Reports --}}
                @canany(['reports.view', 'delivery_logs.view', 'import_logs.view'])
                @php $reportsActive = request()->routeIs('reports.*') || request()->routeIs('import-logs.*'); @endphp
                <li class="nav-item has-treeview @if($reportsActive) menu-is-opening menu-open @endif">
                    <a href="#" class="nav-link @if($reportsActive) active @endif">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>
                            Reports
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview" style="display: @if($reportsActive) block @else none @endif;">
                        @can('reports.view')
                        <li class="nav-item">
                            <a href="{{ route('reports.index') }}" class="nav-link @if(request()->routeIs('reports.index')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Reports</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reports.scan-history') }}" class="nav-link @if(request()->routeIs('reports.scan-history')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Scan History</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reports.guest-redemption') }}" class="nav-link @if(request()->routeIs('reports.guest-redemption')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Guest Redemption</p>
                            </a>
                        </li>
                        @endcan
                        @can('delivery_logs.view')
                        <li class="nav-item">
                            <a href="{{ route('reports.delivery-logs') }}" class="nav-link @if(request()->routeIs('reports.delivery-logs')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Delivery Logs</p>
                            </a>
                        </li>
                        @endcan
                        @can('import_logs.view')
                        <li class="nav-item">
                            <a href="{{ route('import-logs.index') }}" class="nav-link @if(request()->routeIs('import-logs.*')) active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Import Logs</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany

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
