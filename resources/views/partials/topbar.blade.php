<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="{{ __('common.buka_menu') }}">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto">
            @auth
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        {{ auth()->user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ Route::has('profile.edit') ? route('profile.edit') : '#' }}">
                                {{ __('common.profil') }}
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                                @csrf
                                <button type="submit" class="dropdown-item">{{ __('common.keluar') }}</button>
                            </form>
                        </li>
                    </ul>
                </li>
            @endauth
        </ul>
    </div>
</nav>
