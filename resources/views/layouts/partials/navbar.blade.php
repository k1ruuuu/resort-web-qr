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
