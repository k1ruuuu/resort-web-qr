@props([
    'groups' => [],
    'navClass' => 'navbar-nav me-auto',
])

@php
    // Default navigation structure when $groups is empty
    $defaultGroups = [
        [
            'label' => 'Platform',
            'icon' => 'fas fa-tachometer-alt',
            'defaultOpen' => true,
            'active' => ['dashboard', 'docs', 'properties.*', 'rooms.*', 'guests.*', 'bookings.*', 'facilities.*', 'outlets.*', 'vouchers.*'],
            'permissions' => [], // show to everyone
            'items' => [
                ['label' => 'Overview', 'icon' => 'fas fa-tachometer-alt', 'route' => 'dashboard', 'active' => 'dashboard'],
                ['label' => 'Dokumentasi', 'icon' => 'fas fa-book', 'route' => 'docs', 'active' => 'docs'],
                ['label' => 'Properties', 'icon' => 'fas fa-hotel', 'route' => 'properties.index', 'active' => 'properties.*', 'permission' => 'properties.manage'],
                ['label' => 'Rooms', 'icon' => 'fas fa-door-open', 'route' => 'rooms.index', 'active' => 'rooms.*', 'permission' => 'rooms.manage'],
                ['label' => 'Facilities', 'icon' => 'fas fa-swimming-pool', 'route' => 'facilities.index', 'active' => 'facilities.*', 'permission' => 'facilities.manage'],
                ['label' => 'Outlets', 'icon' => 'fas fa-store', 'route' => 'outlets.index', 'active' => 'outlets.*', 'permission' => 'outlets.manage'],
                ['label' => 'Guests', 'icon' => 'fas fa-users', 'route' => 'guests.index', 'active' => 'guests.*', 'permission' => 'guests.manage'],
                ['label' => 'Bookings', 'icon' => 'fas fa-calendar-check', 'route' => 'bookings.index', 'active' => 'bookings.*', 'permission' => 'bookings.view'],
                ['label' => 'Vouchers', 'icon' => 'fas fa-qrcode', 'route' => 'vouchers.index', 'active' => 'vouchers.index', 'permission' => 'vouchers.view'],
                ['label' => 'Redeem QR', 'icon' => 'fas fa-check-circle', 'route' => 'vouchers.redeem.form', 'active' => 'vouchers.redeem.form', 'permission' => 'vouchers.redeem'],
                ['label' => 'Scan QR Code', 'icon' => 'fas fa-camera', 'route' => 'vouchers.scan.form', 'active' => 'vouchers.scan.form', 'permission' => 'vouchers.redeem'],
                ['label' => 'Reports', 'icon' => 'fas fa-chart-bar', 'route' => 'reports.index', 'active' => 'reports.*', 'permission' => 'reports.view'],
                ['label' => 'Scan History', 'icon' => 'fas fa-history', 'route' => 'reports.scan-history', 'active' => 'reports.scan-history', 'permission' => 'reports.view'],
                ['label' => 'Delivery Logs', 'icon' => 'fas fa-truck', 'route' => 'reports.delivery-logs', 'active' => 'reports.delivery-logs', 'permission' => 'delivery_logs.view'],
                ['label' => 'Import Logs', 'icon' => 'fas fa-file-import', 'route' => 'import-logs.index', 'active' => 'import-logs.*', 'permission' => 'import_logs.view'],
            ],
        ],
        [
            'label' => 'Settings',
            'icon' => 'fas fa-cog',
            'defaultOpen' => false,
            'active' => ['settings.delivery', 'users.*', 'roles.*'],
            'permissions' => ['delivery_settings.manage', 'users.manage', 'roles.manage'],
            'items' => [
                ['label' => 'Delivery Settings', 'icon' => 'fas fa-truck', 'route' => 'settings.delivery', 'active' => 'settings.delivery', 'permission' => 'delivery_settings.manage'],
                ['label' => 'Users', 'icon' => 'fas fa-user', 'route' => 'users.index', 'active' => 'users.*', 'permission' => 'users.manage'],
                ['label' => 'Roles', 'icon' => 'fas fa-user-shield', 'route' => 'roles.index', 'active' => 'roles.*', 'permission' => 'roles.manage'],
            ],
        ],
    ];

    $groups = $groups ?: $defaultGroups;
@endphp

<ul class="{{ $navClass }}">
    {{-- Top-level direct links --}}
    @foreach ($groups as $group)
        @if (isset($group['route']))
            @if (isset($group['permission']) && !auth()->user()?->can($group['permission']))
                @continue
            @endif
            <li class="nav-item {{ isset($group['active']) && request()->routeIs($group['active']) ? 'active' : '' }}">
                <a class="nav-link {{ request()->routeIs($group['active'] ?? $group['route']) ? 'active' : '' }}" href="{{ route($group['route']) }}">
                    @isset($group['icon'])
                        <i class="{{ $group['icon'] }}"></i>
                    @endisset
                    {{ $group['label'] }}
                </a>
            </li>
        @else
            @if (!empty($group['permissions']) && !auth()->user()?->canAny($group['permissions']))
                @continue
            @endif

            @php
                // Filter out items the user cannot access
                $visibleItems = array_filter($group['items'] ?? [], function ($item) {
                    return empty($item['permission']) || auth()->user()?->can($item['permission']);
                });
            @endphp
            @if (empty($visibleItems))
                @continue
            @endif

            @php
                $isActive = isset($group['active']) && request()->routeIs($group['active']);
                $open = $group['defaultOpen'] ?? false;
                $groupId = 'cg-' . Str::slug($group['label']);
            @endphp

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ $isActive ? 'active' : '' }}"
                   href="#"
                   role="button"
                   data-bs-toggle="collapse"
                   data-bs-target="#{{ $groupId }}"
                   aria-expanded="{{ ($open || $isActive) ? 'true' : 'false' }}"
                   aria-controls="{{ $groupId }}">
                    @isset($group['icon'])
                        <i class="{{ $group['icon'] }}"></i>
                    @endisset
                    {{ $group['label'] }}
                    <i class="fas fa-chevron-right nav-chevron {{ ($open || $isActive) ? 'open' : '' }}"></i>
                </a>

                <div id="{{ $groupId }}" class="collapse nav-collapse {{ ($open || $isActive) ? 'show' : '' }}">
                    <ul class="nav flex-column">
                        @foreach ($visibleItems as $item)
                            @if (isset($item['permission']) && !auth()->user()?->can($item['permission']))
                                @continue
                            @endif
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs($item['active'] ?? $item['route']) ? 'active' : '' }}"
                                   href="{{ route($item['route']) }}">
                                    @isset($item['icon'])
                                        <i class="{{ $item['icon'] }}"></i>
                                    @endisset
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </li>
        @endif
    @endforeach
</ul>