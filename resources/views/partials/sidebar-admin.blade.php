<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ url('/') }}" class="brand-link">
            <span class="brand-text fw-light">{{ config('app.name') }}</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                <li class="nav-item">
                    <a href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : '#' }}"
                        class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-gauge"></i>
                        <p>{{ __('common.dashboard') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('admin.unit.index') ? route('admin.unit.index') : '#' }}"
                        class="nav-link {{ request()->routeIs('admin.unit.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sitemap"></i>
                        <p>{{ __('unit.title') }}</p>
                    </a>
                </li>
                @role('super_admin')
                    <li class="nav-item">
                        <a href="{{ Route::has('admin.user.index') ? route('admin.user.index') : '#' }}"
                            class="nav-link {{ request()->routeIs('admin.user.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>{{ __('user.title') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ Route::has('admin.kamus.index') ? route('admin.kamus.index') : '#' }}"
                            class="nav-link {{ request()->routeIs('admin.kamus.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book"></i>
                            <p>{{ __('kamus.title') }}</p>
                        </a>
                    </li>
                @endrole
                <li class="nav-item">
                    <a href="{{ Route::has('admin.pejabat.index') ? route('admin.pejabat.index') : '#' }}"
                        class="nav-link {{ request()->routeIs('admin.pejabat.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-tie"></i>
                        <p>{{ __('pejabat.title') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('admin.kategori.index') ? route('admin.kategori.index') : '#' }}"
                        class="nav-link {{ request()->routeIs('admin.kategori.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tags"></i>
                        <p>{{ __('kategori.title') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('admin.persyaratan.index') ? route('admin.persyaratan.index') : '#' }}"
                        class="nav-link {{ request()->routeIs('admin.persyaratan.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-circle-check"></i>
                        <p>{{ __('syarat.title') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ Route::has('admin.konfigurasi.edit') ? route('admin.konfigurasi.edit') : '#' }}"
                        class="nav-link {{ request()->routeIs('admin.konfigurasi.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-gear"></i>
                        <p>{{ __('konfigurasi.title') }}</p>
                    </a>
                </li>
                {{--
                    Menu modul lain (master data, template, permohonan, arsip, surat
                    masuk/keluar) ditambahkan per fitur seiring route-nya dibuat,
                    digating role/permission (ARCHITECTURE §8) mulai M0-T8/M1-T7.
                --}}
            </ul>
        </nav>
    </div>
</aside>
