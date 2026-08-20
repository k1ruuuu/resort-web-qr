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
    {{-- Sidebar layout: left nav, no topbar --}}
    <div class="sidebar-backdrop" id="sidebarBackdrop" style="display:none;"></div>
    <div class="sidebar-layout">
        <aside class="main-sidebar" id="mainSidebar">
            {{-- Brand --}}
            <a href="{{ route('dashboard') }}" class="brand-link">
                <img src="{{ asset('img/chanaya-logo.png') }}" alt="Logo" class="brand-image">
                <span class="brand-text">E-Voucher</span>
            </a>

            {{-- Logout at bottom --}}
            <div class="sidebar-nav-wrap">
                <x-ui.collapsible-nav
                    navClass="sidebar-nav"
                />
            </div>

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}" class="d-inline w-100">
                    @csrf
                    <button type="submit" class="btn sidebar-logout w-100">
                        <i class="fas fa-power-off"></i> Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid d-flex align-items-center justify-content-between gap-2">
                    <button type="button" class="btn btn-outline-primary sidebar-toggle d-lg-none" id="sidebarToggle" aria-label="Toggle navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="m-0 me-auto">@yield('page_title')</h1>
                </div>
            </div>
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
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
    (function () {
        var sidebar = document.getElementById('mainSidebar');
        var backdrop = document.getElementById('sidebarBackdrop');
        var toggle = document.getElementById('sidebarToggle');

        function openSidebar() {
            if (!sidebar) return;
            sidebar.classList.add('sidebar-open');
            if (backdrop) backdrop.style.display = 'block';
        }
        function closeSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove('sidebar-open');
            if (backdrop) backdrop.style.display = 'none';
        }

        if (toggle) toggle.addEventListener('click', openSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        // Close automatically when a nav link is clicked (mobile)
        document.querySelectorAll('.main-sidebar a').forEach(function (a) {
            a.addEventListener('click', function () {
                if (window.innerWidth < 992) closeSidebar();
            });
        });
    })();
</script>
@stack('scripts')
</body>
</html>