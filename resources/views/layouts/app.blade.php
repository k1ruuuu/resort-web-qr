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
    <nav class="main-header navbar navbar-expand-lg">
        <div class="container-fluid">
            <a href="{{ route('dashboard') }}" class="navbar-brand">
                <img src="{{ asset('img/chanaya-logo.png') }}" alt="Logo" class="brand-image">
                <span class="brand-text">E-Voucher</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <x-ui.collapsible-nav />

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