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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" integrity="sha384-qrt37eUXKQgF1p6OlpdB29OTyKryxbxdJHkvfVN4suujWnn6PibIvbnygcK4uJfA" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-forest.css') }}">
</head>
<body class="hold-transition layout-fixed">
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

    <!-- Toast Container for Live Scan Notifications -->
    <div id="liveScanToastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 99999; margin-top: 50px; pointer-events: none;"></div>

    <!-- Global Interactive Confirmation Modal for Delete & Edit -->
    <div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-labelledby="globalConfirmModalLabel" aria-hidden="true" style="z-index: 100000;">
        <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 380px;">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header border-0 pb-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div id="confirmModalIconWrapper" class="d-inline-flex align-items-center justify-content-center rounded-circle me-2" style="width: 36px; height: 36px; background-color: #fee2e2;">
                            <i id="confirmModalIcon" class="fas fa-trash-alt text-danger"></i>
                        </div>
                        <h6 class="modal-title fw-bold mb-0 text-dark" id="globalConfirmModalLabel">Konfirmasi Hapus</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-3 py-3">
                    <p id="confirmModalMessage" class="text-secondary small mb-0">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer border-0 pt-0 pb-3 px-3 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="confirmModalActionBtn" class="btn btn-sm btn-danger px-3 shadow-sm">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js" integrity="sha384-GzAyPc+9MeNdsDGfpe/gNkeDXXSbdZdY0yKEFBGFxqmq/97NJ92k5oyF1YPOOhm5" crossorigin="anonymous"></script>
<script nonce="{{ $cspNonce }}">
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

    // Real-time Live Scan Notification (Toast in Top Right)
    (function() {
        let lastScanId = 0;
        let isInitialLoad = true;
        const toastContainer = document.getElementById('liveScanToastContainer');
        if (!toastContainer) return;

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function getStatusConfig(scanResult) {
            switch(scanResult) {
                case 'success':
                    return {
                        badgeClass: 'bg-success text-white',
                        borderClass: 'border-start border-success border-4',
                        iconClass: 'fas fa-check-circle text-success'
                    };
                case 'not_found':
                case 'invalid_outlet':
                case 'facility_not_linked':
                    return {
                        badgeClass: 'bg-danger text-white',
                        borderClass: 'border-start border-danger border-4',
                        iconClass: 'fas fa-times-circle text-danger'
                    };
                case 'quota_exceeded':
                case 'invalid_date':
                case 'booking_not_checked_in':
                case 'outside_stay_period':
                case 'voucher_not_active':
                    return {
                        badgeClass: 'bg-warning text-dark',
                        borderClass: 'border-start border-warning border-4',
                        iconClass: 'fas fa-exclamation-triangle text-warning'
                    };
                default:
                    return {
                        badgeClass: 'bg-secondary text-white',
                        borderClass: 'border-start border-secondary border-4',
                        iconClass: 'fas fa-info-circle text-secondary'
                    };
            }
        }

        function createToastElement(scan) {
            const config = getStatusConfig(scan.scan_result);
            const toastEl = document.createElement('div');
            toastEl.className = 'toast show shadow-lg mb-2 ' + config.borderClass;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            toastEl.style.pointerEvents = 'auto';
            toastEl.style.minWidth = '320px';
            toastEl.style.backgroundColor = '#ffffff';
            toastEl.style.borderRadius = '8px';
            toastEl.style.transition = 'all 0.3s ease';

            let guestRoomInfo = '';
            if (scan.guest_name && scan.guest_name !== '-') {
                guestRoomInfo = '<div class="text-muted small mt-1"><i class="fas fa-user me-1"></i> ' + escapeHtml(scan.guest_name) + 
                    (scan.room_label ? ' <span class="badge bg-light text-dark border ms-1">Room ' + escapeHtml(scan.room_label) + '</span>' : '') + '</div>';
            }

            toastEl.innerHTML = 
                '<div class="toast-header d-flex justify-content-between align-items-center py-2 px-3 bg-light border-bottom">' +
                    '<div class="d-flex align-items-center">' +
                        '<i class="' + config.iconClass + ' me-2 fa-lg"></i>' +
                        '<strong class="me-auto text-dark" style="font-size: 0.9rem;">Aktivitas Scan Baru</strong>' +
                    '</div>' +
                    '<div class="d-flex align-items-center">' +
                        '<small class="text-muted me-2" style="font-size: 0.75rem;">' + escapeHtml(scan.time) + '</small>' +
                        '<button type="button" class="btn-close btn-close-sm" aria-label="Close"></button>' +
                    '</div>' +
                '</div>' +
                '<div class="toast-body p-3">' +
                    '<div class="d-flex justify-content-between align-items-start mb-1">' +
                        '<div><span class="text-secondary small">Staff:</span> <strong class="text-dark">' + escapeHtml(scan.staff_name) + '</strong></div>' +
                        '<span class="badge ' + config.badgeClass + ' px-2 py-1" style="font-size: 0.75rem;">' + escapeHtml(scan.scan_result_label) + '</span>' +
                    '</div>' +
                    '<div class="text-secondary small"><i class="fas fa-store me-1"></i> ' + escapeHtml(scan.outlet_name) + ' &bull; ' + escapeHtml(scan.facility_name) + '</div>' +
                    guestRoomInfo +
                '</div>';

            const closeBtn = toastEl.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    toastEl.remove();
                });
            }

            setTimeout(function() {
                toastEl.classList.remove('show');
                setTimeout(function() {
                    toastEl.remove();
                }, 300);
            }, 7000);

            return toastEl;
        }

        function checkRecentScans() {
            fetch('{{ route("notifications.recent-scans") }}?after_id=' + lastScanId, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(function(data) {
                if (data && data.latest_id !== undefined) {
                    if (isInitialLoad) {
                        lastScanId = data.latest_id;
                        isInitialLoad = false;
                        return;
                    }

                    if (data.scans && data.scans.length > 0) {
                        data.scans.forEach(function(scan, index) {
                            setTimeout(function() {
                                const toast = createToastElement(scan);
                                toastContainer.appendChild(toast);
                            }, index * 250);
                        });
                        lastScanId = data.latest_id;
                    }
                }
            })
            .catch(function() {
                // Ignore network errors on background poll
            });
        }

        checkRecentScans();
        setInterval(checkRecentScans, 4000);
    })();

    // Global Confirmation Dialog for Delete & Edit Actions
    window.showConfirmDialog = function(options) {
        const modalEl = document.getElementById('globalConfirmModal');
        if (!modalEl) {
            if (typeof options.onConfirm === 'function') options.onConfirm();
            return;
        }
        
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const label = document.getElementById('globalConfirmModalLabel');
        const msg = document.getElementById('confirmModalMessage');
        const icon = document.getElementById('confirmModalIcon');
        const iconWrap = document.getElementById('confirmModalIconWrapper');
        const actionBtn = document.getElementById('confirmModalActionBtn');

        const type = options.type || 'delete';
        
        if (type === 'delete') {
            label.textContent = options.title || 'Konfirmasi Hapus';
            msg.textContent = options.message || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
            icon.className = 'fas fa-trash-alt text-danger';
            iconWrap.style.backgroundColor = '#fee2e2';
            actionBtn.className = 'btn btn-sm btn-danger px-3 shadow-sm';
            actionBtn.textContent = options.confirmText || 'Ya, Hapus';
        } else if (type === 'edit') {
            label.textContent = options.title || 'Konfirmasi Edit Data';
            msg.textContent = options.message || 'Apakah Anda yakin ingin membuka halaman edit untuk mengubah data ini?';
            icon.className = 'fas fa-edit text-warning';
            iconWrap.style.backgroundColor = '#fef3c7';
            actionBtn.className = 'btn btn-sm btn-warning text-dark px-3 shadow-sm';
            actionBtn.textContent = options.confirmText || 'Ya, Buka Edit';
        } else {
            label.textContent = options.title || 'Konfirmasi Tindakan';
            msg.textContent = options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            icon.className = 'fas fa-exclamation-circle text-primary';
            iconWrap.style.backgroundColor = '#e0f2fe';
            actionBtn.className = 'btn btn-sm btn-primary px-3 shadow-sm';
            actionBtn.textContent = options.confirmText || 'Ya, Lanjutkan';
        }

        const newActionBtn = actionBtn.cloneNode(true);
        actionBtn.parentNode.replaceChild(newActionBtn, actionBtn);
        
        newActionBtn.addEventListener('click', function() {
            modal.hide();
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            }
        });

        modal.show();
    };

    // Remove legacy inline onsubmit confirm handlers
    document.querySelectorAll('form[onsubmit*="confirm"]').forEach(function(form) {
        form.removeAttribute('onsubmit');
    });

    // Global Interceptor for Delete Forms & Buttons
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form && form.tagName === 'FORM') {
            const isDeleteForm = form.querySelector('input[name="_method"][value="DELETE"]') || 
                                 (form.action && form.action.includes('destroy')) ||
                                 (form.classList && form.classList.contains('delete-form')) ||
                                 (form.id && form.id.includes('delete-form'));
            
            const isCheckOutForm = form.action && form.action.includes('check-out');

            if (isDeleteForm && !form.dataset.confirmed) {
                e.preventDefault();
                e.stopPropagation();
                
                window.showConfirmDialog({
                    type: 'delete',
                    title: 'Konfirmasi Hapus Data',
                    message: 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
                    confirmText: 'Ya, Hapus',
                    onConfirm: function() {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            } else if (isCheckOutForm && !form.dataset.confirmed) {
                e.preventDefault();
                e.stopPropagation();

                window.showConfirmDialog({
                    type: 'warning',
                    title: 'Konfirmasi Check Out',
                    message: 'Apakah Anda yakin ingin melakukan check-out tamu ini? Voucher QR tidak akan dapat digunakan lagi setelah check-out.',
                    confirmText: 'Ya, Check Out',
                    onConfirm: function() {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            }
        }
    }, true);

    // Global Interceptor for Edit links & buttons
    document.addEventListener('click', function(e) {
        const editLink = e.target.closest('a[href*="/edit"], a.btn-warning, a[title*="Edit"], a[data-action="edit"]');
        if (editLink && !editLink.dataset.confirmed) {
            const href = editLink.getAttribute('href');
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                if (href.includes('/edit') || editLink.classList.contains('btn-warning') || (editLink.title && editLink.title.toLowerCase().includes('edit'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    window.showConfirmDialog({
                        type: 'edit',
                        title: 'Konfirmasi Edit Data',
                        message: 'Apakah Anda yakin ingin membuka halaman edit untuk mengubah data ini?',
                        confirmText: 'Ya, Buka Edit',
                        onConfirm: function() {
                            editLink.dataset.confirmed = 'true';
                            window.location.href = href;
                        }
                    });
                }
            }
        }
    }, true);
});
</script>
@stack('scripts')
</body>
</html>
